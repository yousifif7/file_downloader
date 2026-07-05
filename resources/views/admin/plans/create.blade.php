@extends('layouts.admin')

@section('title', 'New plan')

@section('content')
    <h1 class="text-2xl font-semibold mb-6">New plan</h1>
    @include('admin.plans._form', ['plan' => null, 'platforms' => $platforms])
@endsection
