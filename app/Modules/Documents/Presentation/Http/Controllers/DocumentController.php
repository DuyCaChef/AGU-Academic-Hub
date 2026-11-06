<?php

namespace App\Modules\Documents\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Khoa;
use App\Models\TaiLieu;
use App\Modules\Documents\Application\Actions\AuthorSoftDeleteDocumentAction;
use App\Modules\Documents\Application\Actions\CreateDocumentVersionAction;
use App\Modules\Documents\Application\Actions\DownloadDocumentAction;
use App\Modules\Documents\Application\Actions\PreviewDocumentAction;
use App\Modules\Documents\Application\Actions\SubmitRemovalRequestAction;
use App\Modules\Documents\Application\Actions\UploadDocumentAction;
use App\Modules\Documents\Application\Actions\UpvoteDocumentAction;
use App\Modules\Documents\Application\Queries\CheckDuplicateDocumentQuery;
use App\Modules\Documents\Application\Queries\GetDocumentDetailQuery;
use App\Modules\Documents\Infrastructure\Persistence\DocumentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentRepository $repository
    ) {}

    // Danh sách tài liệu công khai
    public function index(Request $request): InertiaResponse
    {
        $faculties = Khoa::with('monHocs')->get();
        $filters = $request->only(['search', 'category', 'sort', 'course_id']);
        $documents = $this->repository->paginatePublicDocuments($filters, 12);

        return Inertia::render('Documents/Index', [
            'faculties' => $faculties,
            'documents' => $documents,
            'filters'   => $filters,
        ]);
    }

    // Giao diện form tải tài liệu lên
    public function create(): InertiaResponse
    {
        $faculties = Khoa::with('monHocs')->get();

        return Inertia::render('Documents/Create', [
            'faculties' => $faculties,
        ]);
    }

    // API Kiểm tra trước mã băm SHA-256 (Lọc trùng Lớp 1)
    public function checkDuplicate(Request $request, CheckDuplicateDocumentQuery $query): JsonResponse
    {
        $data = $request->validate([
            'ma_mon_hoc'  => ['required', 'integer'],
            'ma_bam_file' => ['required', 'string'],
        ]);

        $result = $query->execute((int) $data['ma_mon_hoc'], $data['ma_bam_file']);

        return response()->json($result, $result['is_duplicate'] ? 409 : 200);
    }

    // Tải lên tài liệu mới
    public function store(Request $request, UploadDocumentAction $action): RedirectResponse
    {
        $data = $request->validate([
            'ma_mon_hoc' => ['required', 'integer', 'exists:mon_hoc,ma_mon_hoc'],
            'tieu_de'    => ['required', 'string', 'max:255'],
            'mo_ta'      => ['nullable', 'string', 'max:5000'],
            'phan_loai'  => ['nullable', 'string', 'max:50'],
            'file'       => ['required', 'file', 'max:51200'], // Tối đa 50MB
        ]);

        $document = $action->execute($data, $request->file('file'), $request->user());

        return redirect()->route('documents.show', $document->getKey())->with(
            'success',
            'Tải tài liệu lên thành công.'
        );
    }

    // Tạo phiên bản mới cho tài liệu
    public function createVersion(Request $request, int $id, CreateDocumentVersionAction $action): RedirectResponse
    {
        $data = $request->validate([
            'tieu_de'   => ['nullable', 'string', 'max:255'],
            'mo_ta'     => ['nullable', 'string', 'max:5000'],
            'phien_ban' => ['required', 'string', 'max:20'],
            'file'      => ['required', 'file', 'max:51200'],
        ]);

        $version = $action->execute($id, $data, $request->file('file'), $request->user());

        return redirect()->route('documents.show', $version->getKey())->with(
            'success',
            'Đã tạo phiên bản mới cho tài liệu thành công.'
        );
    }

    // Xem chi tiết tài liệu
    public function show(int $id, GetDocumentDetailQuery $query): InertiaResponse
    {
        $document = $query->execute($id);

        return Inertia::render('Documents/Show', [
            'document' => $document,
        ]);
    }

    // Xem trước trực tuyến tài liệu PDF
    public function preview(int $id, PreviewDocumentAction $action): Response
    {
        return $action->execute($id);
    }

    // Tải file tài liệu về máy và tự động tăng lượt tải
    public function download(int $id, DownloadDocumentAction $action): Response
    {
        return $action->execute($id);
    }

    // Bình chọn / Thích tài liệu (Upvote)
    public function upvote(Request $request, int $id, UpvoteDocumentAction $action): JsonResponse
    {
        $count = $action->execute($id, $request->user());

        return response()->json([
            'success'   => true,
            'upvotes'   => $count,
        ]);
    }

    // Chỉnh sửa thông tin tài liệu
    public function update(Request $request, int $id): RedirectResponse
    {
        $document = TaiLieu::query()->findOrFail($id);

        abort_unless(
            $document->ma_nguoi_dang === $request->user()->getKey() || $request->user()->laQuanTriVien(),
            403,
            'Bạn không có quyền chỉnh sửa tài liệu này.'
        );

        $data = $request->validate([
            'tieu_de'   => ['required', 'string', 'max:255'],
            'mo_ta'     => ['nullable', 'string', 'max:5000'],
            'phan_loai' => ['nullable', 'string', 'max:50'],
        ]);

        $document->update($data);

        return back()->with('success', 'Đã cập nhật thông tin tài liệu.');
    }

    // Tác giả tự xóa mềm tài liệu trong 24 giờ đầu và lượt tải = 0
    public function destroy(Request $request, int $id, AuthorSoftDeleteDocumentAction $action): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $action->execute($id, $data['reason'], $request->user());

        return back()->with('success', 'Đã chuyển tài liệu vào thùng rác.');
    }

    // Gửi yêu cầu xin gỡ tài liệu đã thuộc quyền sở hữu cộng đồng
    public function requestRemoval(Request $request, int $id, SubmitRemovalRequestAction $action): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ]);

        $action->execute($id, $data['reason'], $request->user());

        return back()->with('success', 'Đã gửi yêu cầu gỡ tài liệu đến hàng đợi xét duyệt.');
    }
}
