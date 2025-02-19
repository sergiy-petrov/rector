<?php

declare (strict_types=1);
namespace RectorPrefix20211201\Symplify\VendorPatches;

use RectorPrefix20211201\Nette\Utils\Strings;
use RectorPrefix20211201\Symplify\VendorPatches\ValueObject\OldAndNewFileInfo;
final class PatchFileFactory
{
    public function createPatchFilePath(\RectorPrefix20211201\Symplify\VendorPatches\ValueObject\OldAndNewFileInfo $oldAndNewFileInfo, string $vendorDirectory) : string
    {
        $newFileInfo = $oldAndNewFileInfo->getNewFileInfo();
        $inVendorRelativeFilePath = $newFileInfo->getRelativeFilePathFromDirectory($vendorDirectory);
        $relativeFilePathWithoutSuffix = \RectorPrefix20211201\Nette\Utils\Strings::lower($inVendorRelativeFilePath);
        $pathFileName = \RectorPrefix20211201\Nette\Utils\Strings::webalize($relativeFilePathWithoutSuffix) . '.patch';
        return 'patches' . \DIRECTORY_SEPARATOR . $pathFileName;
    }
}
