<?php

namespaceDrupal\Tests\one_two\Unit;

/**
 * @coversDefaultClass \Drupal\user\UserAuth
 */
class MockingTest extends UnitTestCase {

    /**
     * @var \Drupal\Core\Entity\EntityManagerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $entityManager;

    /**
     * @var \Drupal\Core\Password\PasswordInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $passwordChecker;

    /**
     * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $entityStorage;

    /**
     * @var \Drupal\user\UserInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $user;

    public function setUp() {
        parent::setUp();

        $this->entityManager = $this->prophesize(EntityManagerInterface::class);
        $this->passwordChecker = $this->prophesize(PasswordInterface::class);
        $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
        $this->user = $this->prophesize(UserInterface::class);
    }

    /**
     * @covers ::authenticate
     * @dataProvider authenticationProvider
     */

    public function testAuthenticate ( $user_id, $username, $password, $correct_password, $expected) {
        $this->entityManager->getStorage('user')
            ->willReturn($this->entityStorage->reveal());

        $this->entityStorage->loadByProperties(['name' => $username])
            ->willReturn([$this->user->reveal()]);

        $this->user->getPassword()
            ->willReturn($correct_password);

        $this->user->id()
            ->willReturn($user_id);

        $this->passwordChecker->check($password, $correct_password)
            ->willReturn($password === $correct_password);

        // We're not testing the password rehashing here.
        $this->passwordChecker->needsRehash($password)
            ->willReturn(FALSE);

        if (empty($username) || empty($password)) {
            $this->entityStorage->loadByProperties(['name' => $username])
                ->shouldNotBeCalled();
        }

        $user_auth = new UserAuth($this->entityManager->reveal(), $this->passwordChecker->reveal());
        $this->assertEquals($expected, $user_auth->authenticate($username, $password));
    }

        /**
         * @covers ::authenticate
         */
    public function authenticationProvider() {
        return [
            // A correct user name and password.
            [1, 'admin', 'hunter2', 'hunter2', 1],
            // Incorrect password, should return FALSE.
            [1, 'admin', 'pass', 'hunter2', FALSE],
            // Missing username, should return FALSE.
            [1, '', 'hunter2', 'hunter2', FALSE],
            // Missing password, should return FALSE.
            [1, 'Majken Ljunggren', '', 'hunter2', FALSE]];
        }
}