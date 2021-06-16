<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\Post;
use App\Entity\Token\Token;
use App\Mailer\MailerInterface;
use App\Manager\PostManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendEmailGroupedPosts extends Command
{
    private MailerInterface $mail;
    private PostManagerInterface $postManager;
    private EntityRepository $tokenRepository;

    public function __construct(
        MailerInterface $mail,
        PostManagerInterface $postManager,
        EntityManagerInterface $entityManager
    ) {
        $this->postManager = $postManager;
        $this->tokenRepository = $entityManager->getRepository(Token::class);
        $this->mail = $mail;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:send-grouped-posts')
            ->setDescription('Send grouped posts to users');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Email has been sent');

        $posts = $this->postManager->getCreatedPostsToday();

        $data = [];

        /** @var Post $post */
        foreach ($posts as $post) {
            $data[$post->getToken()->getName()]['posts'][] = $post;
        }

        foreach ($data as $tokenName => $posts) {
            $token = $this->tokenRepository->findByName($tokenName);
            $users = $token->getUsers();

            foreach ($users as $user) {
                if ($user->getEmail() !== $token->getOwner()->getEmail()) {
                    $this->mail->sendGroupedPosts($user, $tokenName, $posts);
                }
            }
        }

        return 0;
    }
}
