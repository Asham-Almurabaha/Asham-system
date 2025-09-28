<?php

namespace Modules\Expenses\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Expenses\Entities\Expense;

class ExpenseCompletionController extends Controller
{
    public function __invoke(Request $request, Expense $expense): JsonResponse
    {
        if ($expense->manual_outstanding_amount !== null && (float) $expense->manual_outstanding_amount <= 0.0) {
            return response()->json([
                'status' => 'error',
                'message' => __('expenses::expenses.messages.already_completed'),
            ], 422);
        }

        $noteParts = [];

        if (! is_null($expense->amount)) {
            $noteParts[] = __('expenses::expenses.completion.previous_amount', [
                'amount' => number_format((float) $expense->amount, 2),
            ]);
        }

        $dueDate = optional($expense->due_date)->toDateString();
        if (! empty($dueDate)) {
            $noteParts[] = __('expenses::expenses.completion.previous_due_date', [
                'date' => $dueDate,
            ]);
        }

        $noteLine = '';
        if (! empty($noteParts)) {
            $noteLine = __('expenses::expenses.completion.notes_prefix') . ': ' . implode(' | ', $noteParts);
        }

        $existingNotes = trim((string) $expense->notes);
        $notes = $existingNotes;

        if ($noteLine !== '') {
            if ($notes === '') {
                $notes = $noteLine;
            } elseif (strpos($notes, $noteLine) === false) {
                $notes .= PHP_EOL . $noteLine;
            }
        }

        $notes = trim($notes);

        $expense->forceFill([
            'manual_paid_amount' => $expense->amount,
            'manual_outstanding_amount' => 0,
            'notes' => $notes,
        ])->save();

        return response()->json([
            'status' => 'ok',
            'message' => __('expenses::expenses.messages.completed'),
            'notes' => $notes,
            'note_line' => $noteLine,
            'status_label' => __('expenses::expenses.status_labels.completed'),
        ]);
    }
}
