<?php

declare(strict_types=1);

namespace EonX\EasyRandom;

use EonX\EasyRandom\Exceptions\InvalidAlphabetException;
use EonX\EasyRandom\Exceptions\InvalidAlphabetNameException;
use EonX\EasyRandom\Exceptions\InvalidRandomStringException;
use EonX\EasyRandom\Interfaces\RandomStringInterface;
use Symfony\Component\String\ByteString;

final class RandomString implements RandomStringInterface
{
    /**
     * @var \EonX\EasyRandom\Interfaces\RandomStringConstraintInterface[]
     */
    private $constraints;

    /**
     * @var bool[]
     */
    private $excludes = [];

    /**
     * @var bool[]
     */
    private $includes = [];

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $maxAttempts = 100;

    /**
     * @var string
     */
    private $overrideAlphabet;

    /**
     * @var string
     */
    private $randomString;

    /**
     * @var string
     */
    private $resolvedAlphabet;

    public function __construct(int $length)
    {
        $this->length = $length;

        foreach (self::ALPHABET_NAMES as $alphabet) {
            $this->include($alphabet);
        }
    }

    public function __toString(): string
    {
        return $this->randomString();
    }

    public function alphabet(string $alphabet): RandomStringInterface
    {
        $this->overrideAlphabet = $this->validateAlphabet($alphabet);

        return $this;
    }

    public function clear(): RandomStringInterface
    {
        $this->includes = [];
        $this->excludes = [];

        return $this;
    }

    /**
     * @param \EonX\EasyRandom\Interfaces\RandomStringConstraintInterface[] $constraints
     */
    public function constraints(array $constraints): RandomStringInterface
    {
        $this->constraints = $constraints;

        return $this;
    }

    public function exclude(string $alphabetName): RandomStringInterface
    {
        $this->excludes[$alphabetName] = true;

        return $this;
    }

    public function excludeAmbiguous(): RandomStringInterface
    {
        $this->exclude(self::AMBIGUOUS);

        return $this;
    }

    public function excludeLowercase(): RandomStringInterface
    {
        $this->exclude(self::LOWERCASE);

        return $this;
    }

    public function excludeNumeric(): RandomStringInterface
    {
        $this->exclude(self::NUMERIC);

        return $this;
    }

    public function excludeSimilar(): RandomStringInterface
    {
        $this->exclude(self::SIMILAR);

        return $this;
    }

    public function excludeSymbol(): RandomStringInterface
    {
        $this->exclude(self::SYMBOL);

        return $this;
    }

    public function excludeUppercase(): RandomStringInterface
    {
        $this->exclude(self::UPPERCASE);

        return $this;
    }

    public function excludeVowel(): RandomStringInterface
    {
        $this->exclude(self::VOWEL);

        return $this;
    }

    public function include(string $alphabetName): RandomStringInterface
    {
        $this->includes[$alphabetName] = true;

        unset($this->excludes[$alphabetName]);

        return $this;
    }

    public function includeAmbiguous(): RandomStringInterface
    {
        $this->include(self::AMBIGUOUS);

        return $this;
    }

    public function includeLowercase(): RandomStringInterface
    {
        $this->include(self::LOWERCASE);

        return $this;
    }

    public function includeNumeric(): RandomStringInterface
    {
        $this->include(self::NUMERIC);

        return $this;
    }

    public function includeSimilar(): RandomStringInterface
    {
        $this->include(self::SIMILAR);

        return $this;
    }

    public function includeSymbol(): RandomStringInterface
    {
        $this->include(self::SYMBOL);

        return $this;
    }

    public function includeUppercase(): RandomStringInterface
    {
        $this->include(self::UPPERCASE);

        return $this;
    }

    public function includeVowel(): RandomStringInterface
    {
        $this->include(self::VOWEL);

        return $this;
    }

    public function maxAttempts(int $maxAttempts): RandomStringInterface
    {
        $this->maxAttempts = $maxAttempts;

        return $this;
    }

    public function userFriendly(): RandomStringInterface
    {
        $this
            ->include(self::NUMERIC)
            ->include(self::UPPERCASE)
            ->exclude(self::AMBIGUOUS)
            ->exclude(self::LOWERCASE)
            ->exclude(self::SYMBOL)
            ->exclude(self::SIMILAR)
            // Pretty useful to avoid "bad words" in generated strings
            ->exclude(self::VOWEL);

        return $this;
    }

    private function randomString(): string
    {
        if ($this->randomString !== null) {
            return $this->randomString;
        }

        $attempts = 0;

        do {
            $randomString = ByteString::fromRandom($this->length, $this->resolveAlphabet())->toString();
            $attempts++;
        } while ($this->validateString($randomString) === false && $attempts < $this->maxAttempts);

        if ($attempts === $this->maxAttempts) {
            throw new InvalidRandomStringException(\sprintf(
                'Could not generate valid random string for alphabet "%s"',
                $this->resolveAlphabet()
            ));
        }

        return $this->randomString = $randomString;
    }

    private function resolveAlphabet(): string
    {
        if ($this->overrideAlphabet !== null) {
            return $this->overrideAlphabet;
        }

        if ($this->resolvedAlphabet !== null) {
            return $this->resolvedAlphabet;
        }

        $currentAlphabet = [];

        // Includes
        foreach (\array_keys($this->includes) as $alphabet) {
            foreach ($this->resolveAlphabetCharacters($alphabet) as $char) {
                $currentAlphabet[] = $char;
            }
        }

        // Excludes
        foreach (\array_keys($this->excludes) as $alphabet) {
            $currentAlphabet = \array_diff($currentAlphabet, $this->resolveAlphabetCharacters($alphabet));
        }

        return $this->resolvedAlphabet = $this->validateAlphabet(\implode('', $currentAlphabet));
    }

    /**
     * @return string[]
     */
    private function resolveAlphabetCharacters(string $alphabetName): array
    {
        if (isset(self::ALPHABETS[$alphabetName])) {
            return \str_split(self::ALPHABETS[$alphabetName]);
        }

        throw new InvalidAlphabetNameException(\sprintf('Alphabet with name "%s" does not exist', $alphabetName));
    }

    private function validateAlphabet(string $alphabet): string
    {
        if (\strlen($alphabet) > 0) {
            return $alphabet;
        }

        throw new InvalidAlphabetException('Alphabet to generate random string cannot be empty');
    }

    private function validateString(string $randomString): bool
    {
        if ($this->constraints === null) {
            return true;
        }

        foreach ($this->constraints as $constraint) {
            if ($constraint->isValid($randomString) === false) {
                return false;
            }
        }

        return true;
    }
}
