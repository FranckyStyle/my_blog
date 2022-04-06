<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator implements PasswordAuthenticatedInterface
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private UrlGeneratorInterface $urlGenerator;
    private UserPasswordEncoderInterface $passwordEncoder;
    private UserRepository $userRepository;

    
    public function __construct(UrlGeneratorInterface $urlGenerator, UserRepository $userRepository)
    {
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
    }


    public function supports(Request $request): bool
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }


    /**
     * Create a passport for the current request. Here a passport containing the user, the
     * presented password and the CSRF token value.
     * @param Request $request
     * @return PassportInterface
     */
    public function authenticate(Request $request): PassportInterface
    {
        return new Passport(
            new UserBadge($request->request->get('email'), function ($userIdentifier) {
                return $this->userRepository->findOneBy(['email' => $userIdentifier]);
            }),
            new PasswordCredentials($request->request->get('password'))
        );
    }


    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }


    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?RedirectResponse {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->urlGenerator->generate('homepage'));
    }


    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}