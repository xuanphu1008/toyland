@extends('layouts.admin')

@section('title', 'Yêu cầu hỗ trợ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h5 class="text-secondary fw-bold mb-0">Yêu Cầu Hỗ Trợ (Ticket)</h5>
    <div class="d-flex gap-2">
        <span class="badge bg-warning text-dark fs-6 px-3 py-2">Chờ xử lý: {{ $stats['open'] }}</span>
        <span class="badge bg-info text-dark fs-6 px-3 py-2">Đang xử lý: {{ $stats['processing'] }}</span>
        <span class="badge bg-secondary fs-6 px-3 py-2">Đã đóng: {{ $stats['closed'] }}</span>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3">
    <div class="card-body">
        <form action="{{ route('admin.tickets.index') }}" method="GET" class="row g-2 mb-4 bg-light p-3 rounded-3">
            <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Mã ticket, tiêu đề, tên khách..." value="{{ request('search') }}"></div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- Trạng thái --</option>
                    @foreach (\App\Models\Ticket::STATUSES as $key => $label)
                    <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">-- Loại yêu cầu --</option>
                    @foreach (\App\Models\Ticket::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-secondary w-100">Lọc</button></div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Mã</th>
                        <th>Khách hàng</th>
                        <th>Tiêu đề</th>
                        <th class="text-center">Loại</th>
                        <th class="text-center">Mức độ</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Trả lời</th>
                        <th>Ngày gửi</th>
                        <th class="text-end"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                    <tr class="{{ $ticket->priority === 'high' && $ticket->status !== 'closed' ? 'table-warning' : '' }}">
                        <td class="font-monospace small fw-bold">{{ $ticket->code }}</td>
                        <td>{{ $ticket->user->name ?? 'Khách' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($ticket->subject, 50) }}</td>
                        <td class="text-center"><span class="badge bg-light text-dark">{{ $ticket->category_label }}</span></td>
                        <td class="text-center">
                            <span class="badge {{ $ticket->priority === 'high' ? 'bg-danger' : 'bg-secondary' }}">{{ $ticket->priority_label }}</span>
                        </td>
                        <td class="text-center"><span class="badge {{ $ticket->status_color }}">{{ $ticket->status_label }}</span></td>
                        <td class="text-center">{{ $ticket->replies_count }}</td>
                        <td class="small text-nowrap">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.tickets.show', $ticket->id) }}" class="btn btn-outline-primary btn-sm rounded-pill">Xử lý</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-muted text-center py-4">Không có yêu cầu hỗ trợ nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">{{ $tickets->links() }}</div>
    </div>
</div>
@endsection
