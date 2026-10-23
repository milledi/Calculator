<?php
/**
 * Список заявок.
 *
 * @var App\View\View $view
 * @var list<array<string, mixed>> $rows
 * @var array<string, int> $counts
 * @var int $total
 * @var ?App\Application\ApplicationStatus $currentStatus
 * @var ?int $updatedId
 * @var string $csrfToken
 */

use App\Application\ApplicationStatus;
use App\Loan\LoanCalculator;
use App\Loan\LoanType;

$e = $view->e(...);
$sumAmount = array_sum(array_map(static fn (array $r): float => (float) $r['amount'], $rows));
$statusFilter = $currentStatus?->value ?? '';
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Заявки — админка</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='9' fill='%230D6B4F'/%3E%3Cpath d='M9 22 L23 10' stroke='%23C6F36B' stroke-width='3' stroke-linecap='round'/%3E%3Ccircle cx='10.5' cy='11.5' r='2.5' fill='%23fff'/%3E%3Ccircle cx='21.5' cy='20.5' r='2.5' fill='%23fff'/%3E%3C/svg%3E">
<style>
  :root {
    color-scheme: light;
    --canvas: #f4f3ee; --surface: #fff; --subtle: #eeece5;
    --ink: #141816; --muted: #5c6360; --line: #e2dfd6; --line-strong: #ccc8bd;
    --brand: #0d6b4f; --brand-soft: #ddf0e7; --on-brand: #fff; --highlight: #c6f36b;
    --warn-ink: #8a5300; --warn-bg: #fdf0d5;
    --s-new-bg: #e3ecff; --s-new-ink: #1e3a8a;
    --s-in_review-bg: #fdf0d5; --s-in_review-ink: #7a4a00;
    --s-approved-bg: #ddf0e7; --s-approved-ink: #0a5740;
    --s-rejected-bg: #fde2e2; --s-rejected-ink: #991b1b;
    --updated: #f6fbe9;
  }
  @media (prefers-color-scheme: dark) {
    :root {
      color-scheme: dark;
      --canvas: #0b0e0d; --surface: #151a18; --subtle: #1d2421;
      --ink: #ecf1ee; --muted: #9aa6a0; --line: #272f2b; --line-strong: #3e4944;
      --brand: #34c48e; --brand-soft: #143428; --on-brand: #06140e;
      --warn-ink: #f5c66b; --warn-bg: #3a2c10;
      --s-new-bg: #1b2a4d; --s-new-ink: #afc4ff;
      --s-in_review-bg: #3a2c10; --s-in_review-ink: #f5c66b;
      --s-approved-bg: #143428; --s-approved-ink: #7ee2b8;
      --s-rejected-bg: #3d1717; --s-rejected-ink: #fca5a5;
      --updated: #18231c;
    }
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; background: var(--canvas); color: var(--ink);
    font: 15px/1.5 "Onest", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    -webkit-font-smoothing: antialiased;
  }
  a { color: inherit; }
  .wrap { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
  .topbar { border-bottom: 1px solid var(--line); }
  .topbar .wrap { display: flex; align-items: center; justify-content: space-between; height: 68px; }
  .brand { display: flex; align-items: center; gap: 12px; font-weight: 600; text-decoration: none; }
  .tag { font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: 999px; background: var(--brand-soft); color: var(--brand); }
  h1 { margin: 40px 0 4px; font-size: 32px; line-height: 1.15; letter-spacing: -0.02em; font-weight: 650; }
  .sub { margin: 0; color: var(--muted); }
  .tabs { display: flex; flex-wrap: wrap; gap: 8px; margin: 28px 0 20px; }
  .tab {
    display: inline-flex; align-items: center; gap: 8px; min-height: 40px; padding: 0 14px;
    border: 1px solid var(--line); border-radius: 999px; background: var(--surface);
    text-decoration: none; font-weight: 500; font-size: 14px; transition: border-color .15s, background .15s;
  }
  .tab:hover { border-color: var(--line-strong); }
  .tab[aria-current="page"] { background: var(--ink); border-color: var(--ink); color: var(--canvas); }
  .count { font-size: 12px; font-weight: 600; min-width: 22px; padding: 1px 7px; border-radius: 999px; background: var(--subtle); color: var(--muted); text-align: center; }
  .tab[aria-current="page"] .count { background: color-mix(in srgb, var(--canvas) 20%, transparent); color: var(--canvas); }
  .toast {
    display: flex; align-items: center; gap: 10px; margin: 0 0 16px; padding: 12px 16px;
    border-radius: 16px; background: var(--brand-soft); color: var(--brand); font-weight: 500;
  }
  .card { background: var(--surface); border: 1px solid var(--line); border-radius: 24px; overflow: hidden; }
  .scroll { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th {
    text-align: left; font-weight: 500; font-size: 12px; color: var(--muted); white-space: nowrap;
    padding: 14px 16px; border-bottom: 1px solid var(--line); background: var(--surface);
  }
  td { padding: 14px 16px; border-bottom: 1px solid var(--line); vertical-align: middle; }
  tbody tr:last-child td { border-bottom: 0; }
  tbody tr:hover td { background: color-mix(in srgb, var(--subtle) 45%, transparent); }
  tr.is-updated td { background: var(--updated); }
  .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
  .id { color: var(--muted); font-variant-numeric: tabular-nums; }
  .nowrap { white-space: nowrap; }
  .muted { color: var(--muted); }
  .name { font-weight: 600; min-width: 200px; }
  .load { display: block; font-size: 12px; color: var(--muted); }
  .load.warn { display: table; margin: 2px 0 0 auto; padding: 0 6px; border-radius: 6px; background: var(--warn-bg); color: var(--warn-ink); font-weight: 600; }
  .status-form { display: flex; align-items: center; gap: 6px; }
  .status {
    appearance: none; -webkit-appearance: none; font: inherit; font-size: 13px; font-weight: 600;
    min-height: 34px; padding: 0 30px 0 12px; border: 0; border-radius: 999px; cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 11px center;
  }
  .status:focus-visible, .save:focus-visible, .tab:focus-visible, .brand:focus-visible {
    outline: 3px solid color-mix(in srgb, var(--brand) 45%, transparent); outline-offset: 2px;
  }
  <?php foreach (ApplicationStatus::cases() as $s): ?>
  .status-<?= $s->value ?> { background-color: var(--s-<?= $s->value ?>-bg); color: var(--s-<?= $s->value ?>-ink); }
  <?php endforeach; ?>
  .save {
    font: inherit; font-size: 13px; font-weight: 600; min-height: 34px; padding: 0 12px; cursor: pointer;
    border: 1px solid var(--line); border-radius: 999px; background: var(--surface); color: var(--ink);
    transition: border-color .15s, background .15s;
  }
  .save:hover { border-color: var(--line-strong); background: var(--subtle); }
  .empty { padding: 56px 24px; text-align: center; color: var(--muted); }
  .empty strong { display: block; margin-bottom: 4px; color: var(--ink); font-size: 16px; }
  footer.wrap { padding-top: 20px; padding-bottom: 48px; color: var(--muted); font-size: 13px; }
  @media (max-width: 640px) {
    .wrap { padding: 0 16px; }
    h1 { font-size: 26px; margin-top: 28px; }
  }
  @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
</style>
</head>
<body>
<header class="topbar">
  <div class="wrap">
    <a class="brand" href="/admin">
      <svg width="36" height="36" viewBox="0 0 32 32" aria-hidden="true">
        <rect width="32" height="32" rx="9" fill="#0D6B4F"/>
        <path d="M9 22 L23 10" stroke="#C6F36B" stroke-width="3" stroke-linecap="round"/>
        <circle cx="10.5" cy="11.5" r="2.5" fill="#fff"/><circle cx="21.5" cy="20.5" r="2.5" fill="#fff"/>
      </svg>
      Кредитный калькулятор
      <span class="tag">Админка</span>
    </a>
  </div>
</header>

<main class="wrap">
  <h1>Заявки на кредиты</h1>
  <p class="sub">
    <?= $currentStatus ? $e($currentStatus->pluralLabel()) . ': ' : 'Всего: ' ?><?= count($rows) ?>
    <?php if ($rows): ?> · на сумму <?= $e($view->money($sumAmount)) ?><?php endif; ?>
  </p>

  <nav class="tabs" aria-label="Фильтр по статусу">
    <a class="tab" href="/admin"<?= $currentStatus === null ? ' aria-current="page"' : '' ?>>
      Все <span class="count"><?= $total ?></span>
    </a>
    <?php foreach (ApplicationStatus::cases() as $s): ?>
      <a class="tab" href="/admin?status=<?= $e($s->value) ?>"<?= $s === $currentStatus ? ' aria-current="page"' : '' ?>>
        <?= $e($s->pluralLabel()) ?> <span class="count"><?= (int) ($counts[$s->value] ?? 0) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($updatedId !== null): ?>
    <div class="toast" role="status">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
      Статус заявки №<?= (int) $updatedId ?> сохранён
    </div>
  <?php endif; ?>

  <div class="card">
    <?php if (!$rows): ?>
      <div class="empty">
        <strong><?= $currentStatus ? 'В этом статусе заявок нет' : 'Заявок пока нет' ?></strong>
        Новые заявки из калькулятора появятся здесь.
      </div>
    <?php else: ?>
      <div class="scroll">
        <table>
          <thead>
            <tr>
              <th scope="col">№</th>
              <th scope="col">Создана</th>
              <th scope="col">Клиент</th>
              <th scope="col">Продукт</th>
              <th scope="col" class="num">Сумма</th>
              <th scope="col" class="num">Срок</th>
              <th scope="col" class="num">Платёж</th>
              <th scope="col" class="num">Доход</th>
              <th scope="col">Статус</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row):
                $id = (int) $row['id'];
                $income = (float) $row['monthly_income'];
                $load = $income > 0 ? (float) $row['monthly_payment'] / $income : INF;
                $overLimit = $load > LoanCalculator::MAX_PAYMENT_TO_INCOME_RATIO;
                $rowStatus = ApplicationStatus::tryFrom((string) $row['status']);
            ?>
            <tr<?= $id === $updatedId ? ' class="is-updated"' : '' ?>>
              <td class="id"><?= $id ?></td>
              <td class="nowrap muted"><?= $e($view->dateTime((string) $row['created_at'])) ?></td>
              <td class="name"><?= $e($row['full_name']) ?></td>
              <td class="nowrap"><?= $e(LoanType::titleOf((string) $row['loan_type'])) ?></td>
              <td class="num"><?= $e($view->money($row['amount'])) ?></td>
              <td class="num"><?= (int) $row['term_months'] ?>&nbsp;мес.</td>
              <td class="num"><?= $e($view->money($row['monthly_payment'])) ?></td>
              <td class="num">
                <?= $e($view->money($income)) ?>
                <span class="load<?= $overLimit ? ' warn' : '' ?>" title="Доля платежа в доходе">
                  <?= is_finite($load) ? (int) ceil($load * 100) . '%' : '—' ?><?= $overLimit ? ' — выше 50%' : '' ?>
                </span>
              </td>
              <td>
                <form class="status-form" method="post" action="/admin/<?= $id ?>/status">
                  <input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>">
                  <input type="hidden" name="filter" value="<?= $e($statusFilter) ?>">
                  <select name="status" class="status status-<?= $e($rowStatus?->value ?? 'new') ?>" aria-label="Статус заявки №<?= $id ?>">
                    <?php foreach (ApplicationStatus::cases() as $s): ?>
                      <option value="<?= $e($s->value) ?>"<?= $s === $rowStatus ? ' selected' : '' ?>><?= $e($s->label()) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="save">Сохранить</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</main>

<footer class="wrap">Суммы округлены до рубля. Нагрузка — доля ежемесячного платежа в доходе клиента, допустимо до 50%.</footer>
</body>
</html>
