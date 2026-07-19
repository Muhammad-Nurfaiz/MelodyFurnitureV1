@extends('admin.layouts.app')

@section('title','Edit Produk')

@section('content')

<form
    action="{{ route('admin.products.update',$product) }}"
    method="POST"
    enctype="multipart/form-data">

    @include('admin.modules.product._form')

</form>

@endsection