<?php

declare(strict_types=1);

namespace App\Controllers\Api;


use App\Core\Request;
use App\Core\Response;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Agency as AgencyModel;

class AgencyApiController extends Controller
{
    public function __construct(
        \App\Core\Request $request,
        \App\Core\Response $response,
        Database $db
    ) {
        parent::__construct($request, $response, $db);
    }

    public function index(): void
    {
        $page = max(1, (int) $this->request->get('page', 1));
        $rows = (new AgencyModel($this->db))->listAgencies($page, 20, [
            'search' => $this->request->get('q'),
            'verified' => $this->request->get('verified'),
        ]);
        $this->success($rows);
    }

    public function show(string $id): void
    {
        $a = (new AgencyModel($this->db))->find((int) $id) ?? (new AgencyModel($this->db))->findBy('slug', $id);
        if (!$a) { $this->json(['error' => 'Agency not found'], 404); return; }
        $a['members'] = (new AgencyModel($this->db))->listMembers((int) $a['id']);
        $this->success($a);
    }

    public function join(string $id): void
    {
        $me = $this->user();
        $agencyModel = new AgencyModel($this->db);
        if ($agencyModel->isMember((int) $id, (int) $me['id'])) {
            $this->fail('Already a member', [], 400); return;
        }
        $agencyModel->join((int) $id, (int) $me['id']);
        $this->success(null, 'Request submitted');
    }
}
