<?php
/**
 * Created by PhpStorm.
 * User: sji
 * Date: 19/03/16
 * Time: 10:30
 */

namespace Nes;

class ThreadedRenderer
{
    /**
     * ThreadedRenderer constructor.
     */
    public function __construct()
    {
        global $renderer;
        $this->renderer = $renderer;
    }

    public function render($serializedFrame)
    {
        global $renderer;
        $currentFrame = unserialize($serializedFrame);
        $renderer->render($currentFrame);
    }
}