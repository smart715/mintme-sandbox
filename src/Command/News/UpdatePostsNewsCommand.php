<?php declare(strict_types = 1);

namespace App\Command\News;

use App\Entity\News\Post;
use App\Manager\PostNewsManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class UpdatePostsNewsCommand extends Command
{
    private PostNewsManagerInterface $postNewsManager;
    private CacheInterface $cache;

    public function __construct(
        PostNewsManagerInterface $postNewsManager,
        CacheInterface $cache
    ) {
        $this->postNewsManager = $postNewsManager;
        $this->cache = $cache;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:update-posts-news')
            ->setDescription('Update posts news in cache.')
            ->addArgument('time', InputArgument::REQUIRED, 'Time expressed in seconds.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $time */
        $time = $input->getArgument('time');

        $this->cache->delete(Post::RECOMMENDED_CACHE_KEY);
        $this->generatePostsNews((int)$time);

        $output->writeln('The cache has been updated.');

        return 0;
    }

    private function generatePostsNews(int $time): void
    {
        $this->cache->get(
            Post::RECOMMENDED_CACHE_KEY,
            function (ItemInterface $item) use ($time) {
                $item->expiresAfter($time);

                return $this->postNewsManager->getRandomPostNews(4);
            }
        );
    }
}
