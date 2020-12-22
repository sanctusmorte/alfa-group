<?php

namespace App\DataFixtures\Magazine;

class MagazineFixturesData
{
    /**
     * @return array
     */
    public function getMagazinesForFixtures(): array
    {
        return [
            0 => [
                'name' => 'Журнал #1',
                'description' => 'Это вот такое краткое описание журнала #1',
                'authorsIds' => [1, 2]
            ],
            1 => [
                'name' => 'Журнал #2',
                'description' => '',
                'authorsIds' => [2]
            ],
            2 => [
                'name' => 'Журнал #3',
                'description' => 'Это вот такое краткое описание журнала #3',
                'authorsIds' => [1, 2, 3, 4]
            ],
            3 => [
                'name' => 'Журнал #4',
                'description' => 'Это вот такое краткое описание журнала #4',
                'authorsIds' => [1, 3]
            ],
        ];
    }
}
