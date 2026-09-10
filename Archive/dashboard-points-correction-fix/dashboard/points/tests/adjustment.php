<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../src/Adjustment.php';
use Participation\Adjustment;
function expectAdjustment(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
expectAdjustment(Adjustment::delta('remove', '13') === -13, 'Removal sign');
expectAdjustment(1088 + Adjustment::delta('remove', '13') === 1075, 'Incident regression');
expectAdjustment(0 + Adjustment::delta('remove', '13') === -13, 'Monthly correction');
expectAdjustment(Adjustment::delta('add', '13') === 13, 'Addition sign');
foreach ([['remove','-13'],['remove','0'],['add','1001'],['add','1.5'],['add','1e2'],['add',[]],['other','13']] as [$operation,$amount]) {
    try { Adjustment::delta($operation,$amount); throw new RuntimeException('Invalid input accepted'); }
    catch (InvalidArgumentException $expected) {}
}
echo "Adjustment sign, bounds and incident arithmetic passed. Database retry/concurrency tests remain separate.\n";
