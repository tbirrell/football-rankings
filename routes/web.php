<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/ranks/all-fbs/week/{week?}', 'RankController@fbsRanks');
Route::get('/ranks/all-fbs', 'RankController@fbsRanks');
Route::get('/', 'RankController@fbsRanks');

Route::get('/ranks/{?type}/week/{week?}', 'RankController@show');
Route::get('/ranks/{?type}', 'RankController@show');

Route::get('/ranks/conference/{conference}/week/{week?}', 'RankController@conferenceRanks');
Route::get('/ranks/conference/{conference}', 'RankController@conferenceRanks');


Route::group(['middleware' => ['auth']], function () {
  Route::get('/fetch/rank/cfp', 'RankController@fetchCfp');
  Route::get('/fetch/rank/ap', 'RankController@fetchAp');
  Route::get('/fetch/rank/coaches', 'RankController@fetchCoaches');
});
