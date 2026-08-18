<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Response;

/**
 * Controller khung cho Khối 6 — chỉ đủ để chứng minh cơ chế tenant isolation
 * (global scope + policy) hoạt động qua route thật. CRUD đầy đủ (index có
 * search/filter/sort, form tạo/sửa...) sẽ được xây dựng ở Khối 7.
 */
class MemberController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Member::class);

        return response('Danh sách hội viên (CRUD đầy đủ ở Khối 7).');
    }

    public function show(Member $member): Response
    {
        $this->authorize('view', $member);

        return response("Hội viên: {$member->member_code}");
    }
}
