<?php

namespace Starline;

/**
 * Interface LoggerInterface
 * @package Starline
 * @author kowapssupport@gmail.com
 */
interface LoggerInterface {

    public function logError(string $error_message, array $params): bool;

}
