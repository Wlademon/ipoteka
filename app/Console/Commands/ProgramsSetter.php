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

    public function info($string, $verbosity = null)
    {
        Log::info($string);
        parent::info($string, $verbosity);
    }

    public function alert($string)
    {
        Log::warning($string);
        parent::alert($string);
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
        if (!file_exists(storage_path($this->option('path')))) {
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
                'description' => $p->description,
                'risks' => $p->risks,
                'issues' => $p->issues,
                'conditions' => $p->conditions,
                'is_property' => (bool)$p->isProperty,
                'is_life' => (bool)$p->isLife,
                'is_title' => (bool)$p->isTitle,
                'is_active' => (bool)$p->isActive,
                'is_recommended' => (bool)$p->isRecommended,
            ]);
            $program->owners()->sync($owner->id);
            $company->save();
            $program->save();
            $this->info('add Program ' . $p->programName);
        }
        $this->info('Command Programs Setter FINISHED');
    }
}
