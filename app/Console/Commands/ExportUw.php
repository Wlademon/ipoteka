<?php

namespace App\Console\Commands;

use App\Helpers\Helper;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Class ExportUw
 *
 * @package App\Console\Commands
 */
class ExportUw extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:uw';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export Contracts to UW';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Start Export to UW (NS)");
        Log::info("Start Export to UW (NS)");

        $list = Contract::where('status', Contract::STATUS_CONFIRMED)->whereNull('uw_contract_id')->get();

        foreach ($list as $contract) {
            $resUwin = Helper::getUwinContractId($contract);

            if (isset($resUwin->contractId)) {
                $contract->uw_contract_id = $resUwin->contractId;
                $contract->save();
                $this->info("Success. Export Contract №'$resUwin->contractId' to UW");
                Log::info("Success. Export Contract №'$resUwin->contractId' to UW");
            } else {
                $this->error("Error. Export Contract id:'$contract->id' to UW");
                Log::error("Error. Export Contract id:'$contract->id' to UW");
            }
        }
    }
}
