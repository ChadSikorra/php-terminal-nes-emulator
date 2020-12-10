<?php
namespace Nes;

use Nes\Ppu\Canvas\CanvasInterface;
use Nes\Ppu\Canvas\SdlffiCanvas;
use Nes\Ppu\Renderer;
use parallel\Channel;

class Nes
{
    /** @var \Nes\Ppu\Renderer */
    public $renderer;
    private $nesRomFilename;

    public function __construct(CanvasInterface $canvas)
    {
        $this->renderer = new Renderer($canvas);
    }

    /**
     * @param string $nesRomFilename
     * @throws \Exception
     */
    public function load(string $nesRomFilename)
    {
        $this->nesRomFilename = $nesRomFilename;
    }


    /**
     * @throws \Exception
     */
    public function start()
    {
        $runtime = new \parallel\Runtime("rendering_thread_bootstrap.php");
        $channel = Channel::make('frameChannel', 10);
        $channel_notify = Channel::make('notifyChannel', Channel::Infinite);
        $runtime->run(
            function(string $nesRomFilename, Channel $channel, Channel $channel_notify) {
                $processor = new ThreadedProcessor();
                $events = new \parallel\Events();
                $events->addChannel($channel_notify);
                $events->setBlocking(false);
                $processor->load($nesRomFilename);
                $currentFrame = $processor->nextFrame();
                $channel->send($currentFrame);
                $last = floor(microtime(true));
                $fps = $frame = 1;
                while (1) {
                    $currentFrame = $processor->nextFrame();
                    $second = floor(microtime(true));
                    if ($second !== $last) {
                        echo "calculation: " . $fps, PHP_EOL;
                        $fps = $frame;
                        $frame = 0;
                    }
                    $frame++;
                    $last = $second;
                    $event = $events->poll();
                    if (!is_null($event)) {
                        $events->addChannel($channel_notify);
                        $channel->send($currentFrame);
                    }
                }
            },
            [$this->nesRomFilename, $channel, $channel_notify]
        );

        $renderer = $this->renderer;
        $microTime = microtime(true);
        $last = floor($microTime);
        $fps = $frame = 0;
        do {
            $nextFrame = $channel->recv();
            $microTime = microtime(true);
            $second = floor($microTime);
            if ($second !== $last) {
                echo $fps, PHP_EOL;
                $fps = $frame;
                $frame = 0;
            }
            $frame++;

            $last = floor($microTime);
            $renderer->render($nextFrame);
            $channel_notify->send('');
        } while (true);
    }

    public function close()
    {
    }
}
