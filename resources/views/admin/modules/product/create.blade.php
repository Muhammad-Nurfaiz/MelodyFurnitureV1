@extends('admin.layouts.app')

@section('title', 'Tambah Produk')

@section('content')

<form
    action="{{ route('admin.products.store') }}"
    method="POST"
    @submit="isSubmitting = true"
    enctype="multipart/form-data">
    
    @include('admin.modules.product._form')

</form>

@endsection