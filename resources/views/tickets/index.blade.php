@extends('layouts.user')

@section('title', 'Yêu cầu hỗ trợ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h4 class="fw-bold text-secondary mb-0">Yêu cầu hỗ trợ của tôi</h4>
    <a href="{{ route('tickets.create') }}" class="btn btn-primary rounded-pill px-4">+ Tạo yêu cầu mới</a>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã</th>
                        <th class="text-start">Tiêu đề</th>
                        <th>Loại</th>
                        <th>Mức độ</th>
                        <th>Trạng thái</th>
                        <th>Phản hồi</th>
                        <th>Ngày gửi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                    <tr>
                        <td class="fw-bold font-monospace small">{{ $ticket->code }}</td>
                        <td class="text-start">{{ $ticket->subject }}</td>
                        <td><span class="badge bg-light text-dark">{{ $ticket->category_label }}</span></td>
                        <td>
                            <span class="badge {{ $ticket->priority === 'high' ? 'bg-danger' : 'bg-secondary' }}">{{ $ticket->priority_label }}</span>
                        </td>
                        <td><span class="badge {{ $ticket->status_color }}">{{ $ticket->status_label }}</span></td>
                        <td>{{ $ticket->replies_count }}</td>
                        <td class="text-nowrap small">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('tickets.show', $ticket->id) }}" class="btn btn-sm btn-outline-primary rounded-pill">Xem</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="tl-empty ">
                            Bạn chưa gửi yêu cầu hỗ trợ nào.
                            <div class="mt-3"><a href="{{ route('tickets.create') }}" class="btn btn-outline-primary rounded-pill px-4">Tạo yêu cầu đầu tiên</a></div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">{{ $tickets->links() }}</div>
    </div>
</div>
@endsection
