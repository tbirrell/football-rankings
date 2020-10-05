@extends('layouts.app')

@section('content')
<div class="container">
  <h1>All FBS @if($week != 20)- Week {{$week}}@endif</h1>
    <div class="row">
        <table class="table table-striped table-condensed">
          <thead>
            <tr>
              <th>Rank</th>
              <th>School</th>
              <th>Conference</th>
              <th>Record</th>
            </tr>
          </thead>
          <tbody>
            @foreach($teams as $team)
              <tr>
                <td>{{$team->rank}}</td>
                <td><img src="https://d1si3tbndbzwz9.cloudfront.net/football/team/{{$team->the_score_id}}/small_logo.png" style="width: 24px; height: 24px;"> {{$team->school}}</td>
                <td><a href="{{str_replace('!conf!', strtolower(str_replace(' ','-',$team->conference)), $confLink)}}">{{$team->conference}} {{$team->division}}</a></td>
                <td>{{$team->wins}}-{{$team->losses}}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
    </div>
</div>
@endsection
