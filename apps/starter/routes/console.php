<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('about-project', function (): void {
    $this->comment('Free Fire Info Starter using refatbd/laravel-free-fire.');
})->purpose('Show starter project information');
