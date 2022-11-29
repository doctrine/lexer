Note about upgrading: Doctrine uses static and runtime mechanisms to raise
awareness about deprecated code.

- Use of `@deprecated` docblock that is detected by IDEs (like PHPStorm) or
  Static Analysis tools (like Psalm, phpstan)
- Use of our low-overhead runtime deprecation API, details:
  https://github.com/doctrine/deprecations/

# Upgrade to 2.0.0

`AbstractLexer::glimpse()` and `AbstractLexer::peek()` now will return
instances of `Doctrine\Common\Lexer\Token` when passing `true` to
`AbstractLexer::__construct()`.
Not passing `true` is deprecated.
