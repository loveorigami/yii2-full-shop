<?php
/**
 * Created by PhpStorm.
 * User: volynets
 * Date: 03.08.17
 * Time: 16:26
 */

namespace frontend\services\auth;

use common\entities\User;
use frontend\forms\PasswordResetRequestForm;
use frontend\forms\ResetPasswordForm;
use Yii;
use yii\mail\MailerInterface;

class PasswordResetService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function request(PasswordResetRequestForm $form): void
    {
        $user = $this->getByEmail($form->email);

        if (!$user->isActive()) {
            throw new \DomainException('User is not active');
        }



        $user->requestPasswordReset();

        $this->save($user);

        $send = $this->mailer
            ->compose(
                ['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'],
                ['user' => $user]
            )
            ->setTo($user->email)
            ->setSubject('Password reset for ' . Yii::$app->name)
            ->send();

        if (!$send) {
            throw new \RuntimeException('Sending error');
        }

    }

    public function validateToken($token): void
    {
        if (empty($token) || !is_string($token)) {
            throw new \DomainException('Password reset token cancot be blank');
        }
        if (!$this->existsByPasswordResetToken($token)) {
            throw new \DomainException('Wrong password reset token');
        }
    }

    public function reset(string $token, ResetPasswordForm $form):void
    {
        $user = $this->getByPasswordResetToken($token);
        $user->resetPassword($form->password);
        $this->save($user);
    }



}