<?php
/**
 * Created by PhpStorm.
 * User: volynets
 * Date: 03.08.17
 * Time: 13:48
 */

namespace common\tests\unit\entities\User;

use Codeception\Test\Unit;
use common\entities\User;

class RequestSignupTest extends Unit
{
    public function testSuccess()
    {
        $user = User::requestSignup(
          $username = 'username',
          $email = 'email@site.com',
          $password = 'password'
        );

        $this->assertEquals($username, $user->username);
        $this->assertEquals($email, $user->email);
        $this->assertNotEmpty($user->password_hash);
        $this->assertNotEquals($password, $user->password_hash);
        $this->assertNotEmpty($user->created_at);
        $this->assertNotEmpty($user->auth_key);
        $this->assertTrue($user->isActive());
    }
}