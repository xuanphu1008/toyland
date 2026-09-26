{{-- Nạp module chọn Tỉnh/Quận/Phường. Chỉ include ở những trang thực sự cần
     (checkout, hồ sơ) vì các route locations.* nằm sau middleware auth. --}}
<script>
    window.TL_LOCATION_ENDPOINTS = {
        provinces: "{{ route('locations.provinces') }}",
        districts: "{{ route('locations.districts') }}",
        wards: "{{ route('locations.wards') }}"
    };
</script>
<script src="{{ asset('js/toyland-location.js') }}?v={{ filemtime(public_path('js/toyland-location.js')) }}"></script>
