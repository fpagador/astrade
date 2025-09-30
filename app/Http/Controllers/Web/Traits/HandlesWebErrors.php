<?php

namespace App\Http\Controllers\Web\Traits;

use App\Models\Log;
use Illuminate\Validation\ValidationException;
use Throwable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

trait HandlesWebErrors
{
    /**
     * Executes a callback and handles common errors (ModelNotFound, QueryException, Throwable).
     *
     * This method centralizes error handling for controllers that extend `WebController`,
     * allowing custom messages to be displayed to the user and errors to be logged.
     *
     * @param  callable $callback
     * @param  string   $errorRedirect
     * @param  string|null $successMessage
     * @return mixed
     */
    public function tryCatch(callable $callback, string $errorRedirect = '', string $successMessage = null): mixed
    {
        try {
            $result = $callback();

            if ($result instanceof RedirectResponse && $successMessage) {
                return $result->with('success', $successMessage);
            }

            return $result;
        } catch (ModelNotFoundException $e) {
            $this->logRecord('Recurso no encontrado: ',$errorRedirect, $e);
            return redirect($errorRedirect ?: url()->previous())
                ->withErrors(['general' => 'El recurso solicitado no fue encontrado.'])->withInput();
        } catch (QueryException $e) {
            $this->logRecord('Error de base de datos: ',$errorRedirect, $e);
            return redirect($errorRedirect ?: url()->previous())
                ->withErrors(['general' => 'Error de base de datos. Verifique los datos.'])->withInput();
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->logRecord('Error inesperado: ',$errorRedirect, $e);
            return redirect($errorRedirect ?: url()->previous())
                ->withErrors(['general' => 'Ocurrió un error inesperado. Inténtelo de nuevo.'])->withInput();
        }
    }

    /**
     *
     * @param  string $message
     * @param  string   $errorRedirect
     * @param  ModelNotFoundException|QueryException|Throwable $e
     */
    public function logRecord (
        string $message,
        string $errorRedirect,
        ModelNotFoundException|QueryException|Throwable $e
    )
    {
        Log::record($message, $errorRedirect, [
            'exception' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
        ]);
    }
}
