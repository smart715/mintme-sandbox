<?php declare(strict_types = 1);

namespace App\Command;

use App\Entity\News\Post;
use App\Manager\PostNewsManagerInterface;
use App\Manager\TokenManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class PostsAndTokensFooterCommand extends Command
{
    private TokenManagerInterface $tokenManager;
    private PostNewsManagerInterface $postNewsManager;
    private CacheInterface $cache;

    public function __construct(
        TokenManagerInterface $tokenManager,
        PostNewsManagerInterface $postNewsManager,
        CacheInterface $cache
    ) {
        $this->tokenManager = $tokenManager;
        $this->postNewsManager = $postNewsManager;
        $this->cache = $cache;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:update-post-and-tokens')
            ->setDescription('Update the posts-news and tokens in the footer cache.')
            ->addArgument('time', InputArgument::REQUIRED, 'Time expressed in seconds.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $time */
        $time = $input->getArgument('time');

        $this->cache->delete(Post::FOOTER_CACHE_KEY);
        $this->generatePostsNewsAndTokens((int)$time);

        $output->writeln('The cache has been updated.');

        return 0;
    }

    private function generatePostsNewsAndTokens(int $time): void
    {
        $this->cache->get(
            Post::FOOTER_CACHE_KEY,
            function (ItemInterface $item) use ($time) {
                $item->expiresAfter($time);
                
                return [
                    'tokens' => $this->tokenManager->getRandomTokens(2),
                    'postNews' => $this->postNewsManager->getRandomPostNews(2),
                ];
            }
        );
    }
}
