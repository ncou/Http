<?php

declare(strict_types=1);

namespace Chiron\Http;

use Chiron\Container\ContainerAwareInterface;
use Chiron\Container\ContainerAwareTrait;
use Chiron\Http\Exception\Client\BadRequestHttpException;
use Chiron\Injector\Exception\InvocationException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


// TODO : gérer le cas ou la valeur de retour n'est pas un objet de type ResponseInterface, on pourra wrapper le résultat (tableau en json par exemple) pour retourner un objet Resposne.
// https://github.com/top-think/framework/blob/4de6f58c5e12a1ca80c788887b5208a6705f85d3/src/think/route/Dispatch.php#L93
// https://github.com/spiral/framework/blob/d17c175e85165456fbd2d841c8e81165e371675c/src/Router/src/CoreHandler.php#L144
// https://github.com/middlewares/utils/blob/a9ef1e5365020ead0ae343b95602bd877a9bf852/src/CallableHandler.php#L68

// TODO : mieux gérer les exceptions dans le cas ou il y a une erreur lors du $injector->call()    exemple :   https://github.com/spiral/framework/blob/e63b9218501ce882e661acac284b7167b79da30a/src/Hmvc/src/AbstractCore.php#L67       +         https://github.com/spiral/framework/blob/master/src/Router/src/CoreHandler.php#L199

//https://github.com/spiral/framework/blob/d17c175e85165456fbd2d841c8e81165e371675c/src/Http/src/CallableHandler.php#L66

/**
 * Callback wraps arbitrary PHP callback into object matching [[MiddlewareInterface]].
 * Usage example:
 *
 * ```php
 * $middleware = new CallbackMiddleware(function(ServerRequestInterface $request, RequestHandlerInterface $handler) {
 *     if ($request->getParams() === []) {
 *         return new Response();
 *     }
 *     return $handler->handle($request);
 * });
 * $response = $middleware->process(Yii::$app->getRequest(), $handler);
 * ```
 *
 * @see MiddlewareInterface
 */
// TODO : corriger le phpdoc de la classe !!!! Et indiquer qu'elle doit rester en classe NON FINAL !!!!
class CallableHandler implements RequestHandlerInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var callable|array|string a PHP callback matching signature of [RequestHandlerInterface->handle(ServerRequestInterface $request)]]. // TODO : non c'est faux ce n'est pas obligatoirement une signature de type requesthandler !!!!
     */
    protected $callable;

    /**
     * @param callable|array|string $callable A PHP callback matching signature of [RequestHandlerInterface->handle(ServerRequestInterface $request)]]. // TODO : non c'est faux ce n'est pas obligatoirement une signature de type requesthandler !!!!
     */
    public function __construct($callable)
    {
        $this->callable = $callable;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->call($this->callable, $request->getAttributes());
    }

    // TODO : indiquer dans la phpDoc tous les typehints possibles pour $callable !!!
    // TODO : indiquer qu'une exception est levée si le container n'est pas défini !!!
    // TODO : renommer la méthode en perform() ???? ou invoke() ????
    protected function call($callable, $parameters): ResponseInterface
    {
        // TODO : faire un $this->hasContainer et si le résultat est false dans ce cas lever une une HandlerException en indiquant que le container doit être setter pour executer le handler ????
        /*
        if (! $this->hasContainer()) {
            throw new RouteException('Unable to configure route pipeline without associated container');
            // throw new MissingContainerException('Container is missing, use setContainer() method to set it.');
        }*/

        try {
            $response = $this->getContainer()->call($callable, $parameters);
        } catch (InvocationException $e) {
            //https://github.com/spiral/framework/blob/d17c175e85165456fbd2d841c8e81165e371675c/src/Router/src/CoreHandler.php#L200
            // TODO : améliorer le code pour permettre de passer en paramétre l'exception précédente ($e) à cette http exception
            // TODO : il faudrait surement lever une exception NotFoundHttpException dans le cas ou la mathode du callable n'existe pas dans la classe du callable, mais il faut pour cela séparer ce type d'exception dans la classe Injector pour ne pas remonter systématiquement une Exception InvocationException qui gére à la fois les probléme de callable qui n'existent pas et les callables qui n'ont pas le bon nombre d'arguments en paramétres.
            throw new BadRequestHttpException();
        }

        ////https://github.com/spiral/framework/blob/d17c175e85165456fbd2d841c8e81165e371675c/src/Http/src/CallableHandler.php#L66
        // TODO : il faudrait réussir via la reflexion à récupérer la ligne php ou se trouve le callable et utiliser ce file/line dans l'exception, ca serait plus simple à débugger !!! ou à minima si c'est un tableau on affiche le détail du tableau (qui sera au format, [class, 'method'])
        if (! $response instanceof ResponseInterface) {
            // TODO : retourner plutot une HandlerException ????  https://github.com/zendframework/zend-stratigility/blob/master/src/Exception/MissingResponseException.php
            throw new LogicException(sprintf(
                'Decorated callable request handler of type "%s" failed to produce a response.',
                is_object($callable) ? get_class($callable) : gettype($callable)
            ));
        }

        return $response;
    }
}
