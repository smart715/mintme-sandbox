<?php declare(strict_types = 1);

namespace App\Controller\Traits;

/* Requires properties:
   BlacklistManagerInterface $blacklistManager
*/
trait CheckTokenNameBlacklistTrait
{
    private function checkTokenNameBlacklist(string $name): bool
    {
        $name = trim($name);

        $matches = [];
        preg_match("/(\w+)[-\s]+(\w+)/", $name, $matches);
        array_shift($matches);

        $blacklist = $this->blacklistManager->getList("token");
        $firstMatch = false;
        $secondMatch = false;

        foreach ($blacklist as $blist) {
            if ($this->nameMatches($name, $blist->getValue())) {
                return true;
            }

            if (isset($matches[0]) && $this->nameMatches($matches[0], $blist->getValue())) {
                if ($secondMatch) {
                    return true;
                }

                $firstMatch = true;
            }

            if (isset($matches[1]) && $this->nameMatches($matches[1], $blist->getValue())) {
                if ($firstMatch) {
                    return true;
                }

                $secondMatch = true;
            }
        }

        return false;
    }

    private function nameMatches(string $name, string $val): bool
    {
        return false !== strpos(strtolower($name), strtolower($val))
            && (strlen($name) - strlen($val)) <= 1;
    }
}
