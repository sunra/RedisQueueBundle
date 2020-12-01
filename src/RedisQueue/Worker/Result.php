<?php

    namespace Sunra\RedisQueueBundle\RedisQueue\Worker;

    class Result
    {
        /** @var  bool */
        private $ok = false;

        /** @var  string */
        private $comment = '';

        public function __construct($ok, $comment = '')
        {
            $this->ok = $ok;
            $this->comment = $comment;
        }

        public function isOk()
        {
            return $this->ok;
        }

        public function getComment()
        {
            return $this->comment;
        }

    }