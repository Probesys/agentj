<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Referrer
{
    public function __construct(
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'APP_URL')]
        private string $appUrl,
    ) {
    }

    /**
     * Return the referrer URL of the current request.
     *
     * Use this method to get the referrer URL safely.
     *
     * It considers the Referer header in priority. If it's missing, it returns
     * a "pseudo" referrer saved in the session (see the savePseudoReferrer()
     * method).
     *
     * The referrer is always an internal URL or path. If the referrer found in
     * the header/session targets an external domain, the method returns the
     * path to the homepage.
     */
    public function get(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        $lastGetUrl = $session->get('_referrer', '');
        $referrer = $request->headers->get('Referer', $lastGetUrl);

        if ($this->isSafeUrl($referrer)) {
            return $referrer;
        } else {
            return $this->urlGenerator->generate('homepage');
        }
    }

    /**
     * Save the current URI in session to be used later as referrer if the
     * Referer header is missing.
     *
     * The URI is saved only if:
     * - it's a GET request
     * - it has been initiated by the user navigating in the app
     *
     * The method does its best to exclude requests made with JavaScript.
     * In particular, requests performed with the `fetch()` method must send
     * the `X-Requested-With: XMLHttpRequest` header to be ignored by this
     * method.
     */
    public function savePseudoReferrer(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        $fetchMode = $request->headers->get('Sec-Fetch-Mode', 'navigate');
        // If "navigate", the request is initiated by navigation between HTML
        // documents. In our case, it can also be "cors" as Turbo loads pages
        // with fetch requests.
        $isMainNavigation = $fetchMode === 'navigate' || $fetchMode === 'cors';

        $isTurboFrameRequest = $request->headers->get('turbo-frame') !== null;
        $isJSRequest = $isTurboFrameRequest || $request->isXmlHttpRequest();

        if ($request->isMethod('GET') && $isMainNavigation && !$isJSRequest) {
            $session->set('_referrer', $request->getUri());
        }
    }

    /**
     * Return whether the given URL targets the current server.
     *
     * This is used to avoid attacks redirecting users to an external URL.
     *
     * Note that it doesn't check if the path is a valid route.
     */
    private function isSafeUrl(string $url): bool
    {
        $isHttpUrl = str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
        $isAbsolutePath = str_starts_with($url, '/') && !str_starts_with($url, '//');

        if ($isHttpUrl) {
            // If the URL is an HTTP(S) URL, make sure that it targets the application URL.
            $appUrl = rtrim($this->appUrl, '/') . '/';
            return str_starts_with($url, $appUrl);
        } elseif ($isAbsolutePath) {
            return true;
        } else {
            return false;
        }
    }
}
