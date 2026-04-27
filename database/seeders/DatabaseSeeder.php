<?php

namespace Database\Seeders;

use App\Models\Quiz;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $quiz = Quiz::create(['title' => 'Quiz Pengetahuan Umum']);

        $questions = [
            [
                'text' => 'Ibu kota Indonesia adalah?',
                'options' => [
                    ['Bandung', false],
                    ['Jakarta', true],
                    ['Surabaya', false],
                    ['Medan', false],
                ],
            ],
            [
                'text' => 'Berapa hasil dari 7 x 8?',
                'options' => [
                    ['54', false],
                    ['56', true],
                    ['58', false],
                    ['64', false],
                ],
            ],
            [
                'text' => 'Planet terdekat dengan matahari adalah?',
                'options' => [
                    ['Venus', false],
                    ['Bumi', false],
                    ['Merkurius', true],
                    ['Mars', false],
                ],
            ],
        ];

        foreach ($questions as $i => $q) {
            $question = $quiz->questions()->create([
                'text' => $q['text'],
                'order' => $i + 1,
            ]);
            foreach ($q['options'] as $j => [$text, $isCorrect]) {
                $question->options()->create([
                    'text' => $text,
                    'is_correct' => $isCorrect,
                    'order' => $j + 1,
                ]);
            }
        }
    }
}
