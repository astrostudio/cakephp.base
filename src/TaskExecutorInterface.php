<?php
namespace Base;

interface TaskExecutorInterface {

    function execute($cmd);
    function kill($pid);

}