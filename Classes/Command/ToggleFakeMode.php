<?php

declare(strict_types=1);

namespace Plan2net\FakeFal\Command;

use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientUserPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderReadPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use Exception;
use PDO;
use Plan2net\FakeFal\Resource\Core\ResourceFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ToggleFakeMode
 *
 * @author  Ioulia Kondratovitch <ik@plan2.net>
 * @author  Wolfgang Klinger <wk@plan2.net>
 */
final class ToggleFakeMode extends Command
{
    protected static $defaultName = 'fake-fal:toggle';

    protected function configure(): void
    {
        $this->setDescription('Toggle storage fake fal mode (active/inactive)');
        $this->addArgument(
            'storageIdList',
            InputArgument::REQUIRED,
            'Comma separated list of storage IDs'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $storageIdList = $input->getArgument('storageIdList');
        $countAffected = 0;
        $localStorageIds = $storageIds = $this->getAvailableLocalStorageIds();
        if (!empty($storageIdList)) {
            $storageIds = GeneralUtility::intExplode(',', (string) $storageIdList, true);
        }
        foreach ($storageIds as $storageId) {
            if (in_array($storageId, $localStorageIds, true)) {
                try {
                    $this->deleteProcessedFilesAndFolders($storageId);
                } catch (Exception) {
                    // Ignore
                }
            }

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_storage');
            $status = (int) $queryBuilder->select('tx_fakefal_enable')
                ->from('sys_file_storage')->where($queryBuilder->expr()->eq('uid', $storageId))->executeQuery()->fetchOne();

            $countAffected += $queryBuilder->update('sys_file_storage')
                ->set('tx_fakefal_enable', 1 === $status ? 0 : 1)->where($queryBuilder->expr()->eq('uid', $storageId))->executeStatement();
        }
        $output->writeln($countAffected . ' affected storages updated.' . PHP_EOL);

        return 0;
    }

    /**
     * Returns a list of local storage IDs
     */
    private function getAvailableLocalStorageIds(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_storage');

        return $this->getLocalStorageStatement($queryBuilder)->executeQuery()->fetchFirstColumn();
    }

    /**
     * @throws InsufficientFolderAccessPermissionsException
     * @throws InvalidPathException
     * @throws InsufficientUserPermissionsException
     * @throws InsufficientFolderReadPermissionsException
     * @throws FileOperationErrorException
     */
    private function deleteProcessedFilesAndFolders(int $storageId): void
    {
        /** @var ResourceFactory $resourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $storage = $resourceFactory->getStorageObject($storageId);
        $processingFolder = $storage->getProcessingFolder();

        foreach ($processingFolder->getFiles() as $file) {
            $file->delete();
        }
        $subFolders = $storage->getFoldersInFolder($processingFolder);
        foreach ($subFolders as $folder) {
            $storage->deleteFolder($folder, true);
        }

        // Delete processed file database records
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_processedfile');
        $queryBuilder
            ->delete('sys_file_processedfile')->where($queryBuilder->expr()->eq('storage', $storageId))->executeStatement();
    }

    private function getLocalStorageStatement(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder
            ->select('uid')
            ->from('sys_file_storage')
            ->where(
                $queryBuilder->expr()->eq('driver', $queryBuilder->quote('Local'))
            );
    }
}
