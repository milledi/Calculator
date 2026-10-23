<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\ApplicationRepository;
use App\Application\ApplicationStatus;
use App\Http\Request;
use App\Http\Response;
use App\Security\BasicAuth;
use App\Security\Csrf;
use App\View\View;

/** Админка: список заявок и смена статуса. Всё под Basic-авторизацией. */
final class AdminController
{
    /** @param \Closure(): ApplicationRepository $repository */
    public function __construct(
        private readonly \Closure $repository,
        private readonly BasicAuth $auth,
        private readonly Csrf $csrf,
        private readonly View $view,
    ) {
    }

    /** GET /admin?status=new&updated=5 */
    public function index(Request $request): Response
    {
        if (!$this->auth->isAuthorized($request)) {
            return $this->auth->challenge();
        }

        $status = ApplicationStatus::tryFrom($request->query('status') ?? '');
        $repository = ($this->repository)();
        $rows = $repository->list($status);
        $counts = $repository->countByStatus();

        $updatedId = filter_var($request->query('updated'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        $html = $this->view->render('admin', [
            'rows' => $rows,
            'counts' => $counts,
            'total' => array_sum($counts),
            'currentStatus' => $status,
            'updatedId' => $updatedId === false ? null : $updatedId,
            'csrfToken' => $this->csrf->token(),
        ]);

        return self::secure(Response::html($html));
    }

    /** POST /admin/{id}/status */
    public function updateStatus(Request $request, string $id): Response
    {
        if (!$this->auth->isAuthorized($request)) {
            return $this->auth->challenge();
        }
        if (!$this->csrf->isValid($request->input('_csrf'))) {
            return self::secure(Response::text('Форма устарела. Обновите страницу и попробуйте ещё раз.', 403));
        }

        $applicationId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $status = ApplicationStatus::tryFrom($request->input('status') ?? '');
        if ($applicationId === false || $status === null) {
            return self::secure(Response::text('Некорректные данные', 400));
        }

        if (!($this->repository)()->updateStatus($applicationId, $status)) {
            return self::secure(Response::text('Заявка не найдена', 404));
        }

        // Возвращаемся на ту же вкладку фильтра и подсвечиваем изменённую строку
        $params = ['updated' => $applicationId];
        $filter = ApplicationStatus::tryFrom($request->input('filter') ?? '');
        if ($filter !== null) {
            $params = ['status' => $filter->value] + $params;
        }

        return Response::redirect('/admin?' . http_build_query($params));
    }

    private static function secure(Response $response): Response
    {
        return $response
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Referrer-Policy', 'same-origin')
            ->withHeader(
                'Content-Security-Policy',
                "default-src 'none'; style-src 'unsafe-inline'; img-src data:; form-action 'self'; frame-ancestors 'none'; base-uri 'none'",
            );
    }
}
