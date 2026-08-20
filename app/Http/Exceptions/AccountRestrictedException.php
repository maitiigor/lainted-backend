<?php

namespace App\Http\Exceptions;

use Exception;

/**
 * Distinct from a plain AuthorizationException (e.g.
 * StoreListingRequest's seller-profile gate) so the mobile client can
 * safely react to *this* 403 specifically - clearing the local session
 * and redirecting to login - without misfiring on unrelated 403s. See
 * the render() registered in bootstrap/app.php for the extra `code`
 * field this adds to the JSON response.
 *
 * Deliberately extends the plain base Exception, not
 * AuthorizationException - Laravel's Handler::prepareException()
 * converts AuthorizationException into AccessDeniedHttpException BEFORE
 * custom render() callbacks are checked, which would make a renderer
 * registered for this class never match.
 */
class AccountRestrictedException extends Exception
{
    //
}
