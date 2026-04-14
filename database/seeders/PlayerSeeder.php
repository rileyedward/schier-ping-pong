<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $players = [
            ['first_name' => 'Alec',     'last_name' => 'Vemmer',      'rating' => 1101],
            ['first_name' => 'Andy',     'last_name' => 'Carbajo',     'rating' => 991],
            ['first_name' => 'Annie',    'last_name' => 'Kent',        'rating' => 720],
            ['first_name' => 'Ben',      'last_name' => 'Brown',       'rating' => 1017],
            ['first_name' => 'Ben',      'last_name' => 'Ismert',      'rating' => 718],
            ['first_name' => 'Ben',      'last_name' => 'Koch',        'rating' => 815],
            ['first_name' => 'Bob',      'last_name' => 'Lafferty',    'rating' => 626],
            ['first_name' => 'Brett',    'last_name' => 'Gaynor',      'rating' => 528],
            ['first_name' => 'Brett',    'last_name' => 'Ismert',      'rating' => 776],
            ['first_name' => 'Brian',    'last_name' => 'Georgie',     'rating' => 1247],
            ['first_name' => 'Cameron',  'last_name' => 'Sinclair',    'rating' => 1079],
            ['first_name' => 'Charlie',  'last_name' => 'Ismert',      'rating' => 788],
            ['first_name' => 'Chris',    'last_name' => 'Enochs',      'rating' => 1000],
            ['first_name' => 'Chris',    'last_name' => 'Tucker',      'rating' => 614],
            ['first_name' => 'Cierra',   'last_name' => 'Paul',        'rating' => 631],
            ['first_name' => 'Cole',     'last_name' => 'Fincham',     'rating' => 400],
            ['first_name' => 'Crystal',  'last_name' => 'Manes',       'rating' => 562],
            ['first_name' => 'David',    'last_name' => 'Stein',       'rating' => 891],
            ['first_name' => 'Derek',    'last_name' => 'Maybell',     'rating' => 575],
            ['first_name' => 'Eli',      'last_name' => 'Ismert',      'rating' => 1400],
            ['first_name' => 'Emily',    'last_name' => 'Durham',      'rating' => 682],
            ['first_name' => 'Gabriel',  'last_name' => 'Shaver',      'rating' => 600],
            ['first_name' => 'Grahm',    'last_name' => 'Heide',       'rating' => 1489],
            ['first_name' => 'Greg',     'last_name' => 'Bortnick',    'rating' => 600],
            ['first_name' => 'Greg',     'last_name' => 'Feiten',      'rating' => 983],
            ['first_name' => 'Greg',     'last_name' => 'Tomlinson',   'rating' => 947],
            ['first_name' => 'Hector',   'last_name' => 'Hector',      'rating' => 1000],
            ['first_name' => 'Henry',    'last_name' => 'Ismert',      'rating' => 1394],
            ['first_name' => 'Josh',     'last_name' => 'Meeks',       'rating' => 1122],
            ['first_name' => 'Justin',   'last_name' => 'Monaco',      'rating' => 1236],
            ['first_name' => 'Kahn',     'last_name' => 'Nguyen',      'rating' => 1440],
            ['first_name' => 'Kerry',    'last_name' => 'Wadley',      'rating' => 672],
            ['first_name' => 'Liam',     'last_name' => 'Delaney',     'rating' => 701],
            ['first_name' => 'Luke',     'last_name' => 'Bettis',      'rating' => 835],
            ['first_name' => 'Luke',     'last_name' => 'Ismert',      'rating' => 1415],
            ['first_name' => 'Luke',     'last_name' => 'Porteous',    'rating' => 707],
            ['first_name' => 'Manuel',   'last_name' => 'Gutierrez',   'rating' => 1021],
            ['first_name' => 'Maria',    'last_name' => 'Ismert',      'rating' => 503],
            ['first_name' => 'Marie',    'last_name' => 'Secrest',     'rating' => 613],
            ['first_name' => 'Matt',     'last_name' => 'Frazee',      'rating' => 685],
            ['first_name' => 'Nikki',    'last_name' => 'Curry',       'rating' => 539],
            ['first_name' => 'Pat',      'last_name' => 'Moore',       'rating' => 1000],
            ['first_name' => 'Patrick',  'last_name' => 'Hogan',       'rating' => 1402],
            ['first_name' => 'Paul',     'last_name' => 'Cavalluzzi',  'rating' => 1519],
            ['first_name' => 'Riley',    'last_name' => 'Grotenhuis',  'rating' => 600],
            ['first_name' => 'Rob',      'last_name' => 'Parten',      'rating' => 990],
            ['first_name' => 'Ryan',     'last_name' => 'Courneya',    'rating' => 516],
            ['first_name' => 'Ryan',     'last_name' => 'Haverstick',  'rating' => 734],
            ['first_name' => 'Scott',    'last_name' => 'Copp',        'rating' => 565],
            ['first_name' => 'Sean',     'last_name' => 'Duffy',       'rating' => 1227],
            ['first_name' => 'Stacy',    'last_name' => 'Wood',        'rating' => 1000],
            ['first_name' => 'Timmera',  'last_name' => 'Lindsay',     'rating' => 695],
            ['first_name' => 'Troy',     'last_name' => 'Duvanel',     'rating' => 554],
            ['first_name' => 'Tye',      'last_name' => 'Cooley',      'rating' => 1206],
            ['first_name' => 'Tyler',    'last_name' => 'Epley',       'rating' => 771],
            ['first_name' => 'Tyler',    'last_name' => 'Ismert',      'rating' => 1393],
        ];

        foreach ($players as $data) {
            $email = strtolower($data['first_name']).'.'.strtolower($data['last_name']).'@schier.test';

            Player::create([
                'first_name'     => $data['first_name'],
                'last_name'      => $data['last_name'],
                'email'          => $email,
                'password'       => Hash::make('password'),
                'league_rating'  => $data['rating'],
                'friendly_rating' => 1000,
            ]);
        }
    }
}
