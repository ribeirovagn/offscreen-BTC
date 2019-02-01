<?php

namespace App\Console\Commands;

use App\Http\Controllers\TransactionController;
use Illuminate\Console\Command;

class KeyPoolRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keypool:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Keypool Size';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle()
    {
        $this->updateKeyPoolSize();
    }

    public function updateKeyPoolSize()
    {
        $getwalletinfo = bitcoind()->getwalletinfo();
        $result = $getwalletinfo->get();

        if ($result['keypoolsize'] <= 100) {
            bitcoind()->walletpassphrase(env('BITCOIND_PASSWORD'), 60);
        }
    }
}
