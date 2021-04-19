<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;
use App\Models\Companies;
use App\Models\Programs;
use App\Models\Owners;

class ProgramsSetter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'programs:set {--path=json/programs.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command Programs Setter';

    /**
     * Create a new command instance.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * CBR Daily Currencies Getter
     *
     * Execute the console command.
     *
     * @return mixed
     * @throws Throwable
     */
    public function handle()
    {
        $this->info('Command Programs Setter');
        Log::info('Command Programs Setter');
        if (!file_exists(storage_path($this->option('path')))) {
            Log::warning('Command Programs Setter warning - file not exist.');
            $this->alert('Command Programs Setter warning - file not exist.');
            return false;
        }
        $json = file_get_contents(storage_path($this->option('path')));
        $programs = json_decode($json);

        $owner = Owners::updateOrCreate([
            'code' => 'STRAHOVKA',
        ], [
            'code' => 'STRAHOVKA',
            'name' => 'Страховка.Ру',
            'uwLogin' => 'systemuser1',
        ]);

        foreach ($programs as $p) {
            $company = Companies::updateOrCreate([
                'code' => $p->companyCode,
            ], [
                'name' => $p->companyName,
            ]);

            $program = Programs::updateOrCreate([
                'company_id' => $company->id,
                'program_code' => $p->programCode,
                'program_name' => $p->programName,
            ], [
                'is_active' => $p->isActive,
                'program_uw_code' => $p->programUwCode,
                'description' => $p->description,
                'issues' => $p->issues,
                'conditions' => $p->conditions,
                'matrix' => $p->matrix ?? null,
                'risks' => $p->risks
            ]);
            $program->owners()->sync($owner->id);
            $company->save();
            $program->save();
            $this->info('add Program ' . $p->programName);
            Log::info('add Program ' . $p->programName);
        }
        $this->info('Command Programs Setter FINISHED');
        Log::info('Command Programs Setter FINISHED');
    }
}
