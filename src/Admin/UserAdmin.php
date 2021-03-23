<?php declare(strict_types = 1);

namespace App\Admin;

use App\Admin\Form\PasswordGeneratorButtonType;
use App\Entity\Profile;
use App\Entity\User;
use App\Form\Type\NicknameType;
use App\Manager\ProfileManagerInterface;
use Exception;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManagerInterface;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Route\RouteCollection;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\Form\Validator\ErrorElement;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserAdmin extends AbstractAdmin
{
    /** @var UserManagerInterface */
    private $userManager;

    private ProfileManagerInterface $profileManager;

    protected function configureRoutes(RouteCollection $collection): void
    {
        $collection
            ->remove('delete')
            ->remove('export')
            ->add('reset_password', $this->getRouterIdParameter().'/reset_password');
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        if ('create' == $this->getFormAction()) {
            $formMapper
                ->add('email', null, ['attr' => ['placeholder' => 'Email']])
                ->add('nickname', NicknameType::class, [])
                ->add('plainPassword', TextType::class, [
                    'attr' => [
                        'class' => 'password-generator-input',
                        'placeholder' => 'Password',
                    ],
                ])
                ->add('password', PasswordGeneratorButtonType::class, [
                    'label' => false, // remove label above generate button
                ]);
        }

        $formMapper->add('enabled');

        if ($this->isGranted('EDITROLES')) {
            $formMapper->add('roles', ChoiceType::class, [
                'multiple' => true,
                'choices' => [
                    'USER' => 'ROLE_USER',
                    'ADMIN' => 'ROLE_ADMIN',
                    'SUPPORTER' => 'ROLE_SUPPORTER',
                    'COPYWRITER' => 'ROLE_COPYWRITER',
                    'SUPER_ADMIN' => 'ROLE_SUPER_ADMIN',
                ],
                'help' => '<p>Roles description:</p>'
                    . '<p>USER: Every account has this role by default (user can\'t access support panel and role can\'t be removed)</p>'
                    . '<p>ADMIN: It has \'view\' permission only</p>'
                    . '<p>SUPPORTER: It has \'view\' and limited \'edit\' permissions (supporter can edit only user status, not roles; and reset password)</p>'
                    . '<p>SUPER_ADMIN: It has \'view\' and \'edit\' permissions.</p>'
                    . '<p>COPYWRITER: it has \'view\' and \'edit\' permissions of the Content and Classification panels, it also has \'view\' permissions in the Users Administration panel.</p>',
            ]);
        }
    }

    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('email')
            ->add('roles');
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper->addIdentifier('email', null, ['route' => ['name' => 'show']])
            ->add('roles', 'choice', [
                'multiple' => true,
                'choices' => [
                    'ROLE_USER' => 'USER',
                    'ROLE_ADMIN' => 'ADMIN',
                    'ROLE_SUPPORTER' => 'SUPPORTER',
                    'ROLE_COPYWRITER' => 'COPYWRITER',
                    'ROLE_SUPER_ADMIN' => 'SUPER_ADMIN',
                ],
            ])
            ->add('enabled');

        if ($this->isGranted('EDIT')) {
            $listMapper->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'reset_password' => [
                        'template' => 'admin/list__action_reset_password.html.twig',
                    ],
                ],
            ]);
        }
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('username')
            ->add('email')
            ->add('enabled')
            ->add('roles');
    }

    public function init(UserManagerInterface $userManager, ProfileManagerInterface $profileManager): void
    {
        $this->setUserManager($userManager);
        $this->profileManager = $profileManager;
    }

    /** {@inheritdoc} */
    public function prePersist($user): void
    {
        /** @var UserInterface $user */
        $this->userManager->updatePassword($user);
    }

    /** {@inheritdoc} */
    public function toString($object): string
    {
        return $object instanceof User
            ? $object->getEmail()
            : 'Unknown user';
    }

    private function setUserManager(UserManagerInterface $userManager): void
    {
        if (isset($this->userManager)) {
            throw new Exception('UserManager Dependency is already set');
        }

        $this->userManager = $userManager;
    }

    private function getFormAction(): string
    {
        return $this->id($this->getSubject())
            ? 'edit'
            : 'create';
    }
}
