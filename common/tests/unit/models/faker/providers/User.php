<?php
$faker = Faker\Factory::create();
return [
    'username' => $faker->firstName,
    'email' => $faker->safeEmail,
    'status' => 10,
    'password' => Yii::$app->getSecurity()->generatePasswordHash('password_' . $index),
    'auth_key' => Yii::$app->getSecurity()->generateRandomString(),
    //'intro' => $faker->sentence(7, true),  // generate a sentence with 7 words
];
