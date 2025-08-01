<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class Log extends Model
{
    use HasFactory;
    protected $table = 'logs';
    protected $fillable = ['level', 'message', 'context'];

    protected $casts = [
        'context' => 'array',
    ];

    /**
     * This function records warning, error or info type messages in the log table.
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public static function record(string $level, string $message, array $context = [])
    {
        self::create([
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * This function is used to record in the log table the errors obtained in an Exception
     *
     * @param Request $request
     * @param \Throwable $e
     * @param string $message
     * @param array $extraContext
     */
    public static function exceptionError (Request $request, \Throwable $e, string $message = '', array $extraContext = [])
    {
        $context = array_merge([
            'dni' => $request->has('dni') ? $request->input('dni') : null,
            'user_id' => $request->user()->id ?? null,
            'exception' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
        ], $extraContext);

        self::record('error', $message, $context);
    }
}
