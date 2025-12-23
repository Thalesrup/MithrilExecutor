<?php
// example/StandaloneTask.php

class StandaloneTask {
    public function handle(string $msg) {
        echo " [Standalone] Received: $msg\n";
    }
}
