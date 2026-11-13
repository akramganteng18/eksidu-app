@extends('errors.layout')
@php
    $m = isset($pesan) ? $pesan : (isset($exception) ? $exception->getMessage() : '');
    $generik = ['', 'Forbidden', 'This action is unauthorized.'];
    $pesanTampil = in_array($m, $generik, true) ? 'Anda tidak memiliki izin untuk membuka halaman ini. Jika merasa ini keliru, hubungi admin sekolah.' : $m;
@endphp
@section('art', 'lock')
@section('kode', '403')
@section('judul', 'Akses ditolak')
@section('pesan', $pesanTampil)
