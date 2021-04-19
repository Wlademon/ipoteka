<?php

namespace Tests\Unit;

use Tests\TestCase;

class DictTest extends TestCase
{
    public function testGetDictProgramsSuccess()
    {
        echo PHP_EOL . '====== Start testGetDictProgramsSuccess() =======' . PHP_EOL;
        $response = $this->get('/v1/dict/programs');

        $response
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
        ;
    }

    public function testGetDictSportsSuccess()
    {
        echo PHP_EOL . '====== Start testGetDictSportsSuccess() =======' . PHP_EOL;
        $response = $this->get('/v1/dict/programs');

        $response
            ->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'data']);
    }
}
