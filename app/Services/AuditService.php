<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public function record(?int $userId, string $action, string $entityType, int $entityId, ?array $oldValues, ?array $newValues): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
