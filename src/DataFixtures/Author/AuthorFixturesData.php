<?php

namespace App\DataFixtures\Author;


class AuthorFixturesData
{
    /**
     * @return array
     */
    public function getAuthors(): array
    {
        return [
            0 => [
                'surname' => 'Грознев',
                'name' => 'Иван',
                'patronymic' => 'Васильевич'
            ],
            1 => [
                'surname' => 'Злывко',
                'name' => 'Максим',
                'patronymic' => ''
            ],
            2 => [
                'surname' => 'Милютин',
                'name' => 'Андрей',
                'patronymic' => ''
            ],
            3 => [
                'surname' => 'Краско',
                'name' => 'Анна',
                'patronymic' => 'Владимирована'
            ],
        ];
    }
}
