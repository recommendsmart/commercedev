<?php

namespace Drupal\commerce_file\Controller;

use Drupal\commerce_file\DownloadLoggerInterface;
use Drupal\commerce_file\LicenseFileManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\file\FileInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the controller for downloading licensed files.
 */
class FileDownloadController extends ControllerBase {

  /**
   * The license file manager.
   *
   * @var \Drupal\commerce_file\LicenseFileManagerInterface
   */
  protected $licenseFileManager;

  /**
   * The file download logger.
   *
   * @var \Drupal\commerce_file\DownloadLoggerInterface
   */
  protected $downloadLogger;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new FileDownloadController object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\commerce_file\LicenseFileManagerInterface $license_file_manager
   *   The license file manager.
   * @param \Drupal\commerce_file\DownloadLoggerInterface $download_logger
   *   The download logger.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   */
  public function __construct(AccountInterface $current_user, LicenseFileManagerInterface $license_file_manager, DownloadLoggerInterface $download_logger, StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->currentUser = $current_user;
    $this->licenseFileManager = $license_file_manager;
    $this->downloadLogger = $download_logger;
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('commerce_file.license_file_manager'),
      $container->get('commerce_file.download_logger'),
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Serves the file upon request and record the download.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file being download.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Serve the file as the response.
   *
   * @throws \Exception
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function download(FileInterface $file) {
    $uri = $file->getFileUri();
    $scheme = $this->streamWrapperManager->getScheme($uri);

    // Special handling for Amazon S3.
    if ($scheme === 's3') {
      $licenses = $this->licenseFileManager->getActiveLicenses($file);

      // This should not happen since we're already checking for an active
      // license in our file access logic.
      if (!$licenses) {
        throw new AccessDeniedHttpException();
      }
      $license = reset($licenses);

      // Record the download if the license owner is downloading the file.
      if ($this->licenseFileManager->shouldLogDownload($license)) {
        $this->downloadLogger->log($license, $file);
      }

      return new TrustedRedirectResponse($file->createFileUrl(FALSE));
    }

    if (!$this->streamWrapperManager->isValidScheme($scheme) || !file_exists($uri)) {
      throw new NotFoundHttpException("The file {$uri} does not exist.");
    }

    // Let other modules provide headers and controls access to the file.
    $headers = $this->moduleHandler()->invokeAll('file_download', [$uri]);

    if (!count($headers)) {
      throw new AccessDeniedHttpException();
    }

    foreach ($headers as $result) {
      if ($result == -1) {
        throw new AccessDeniedHttpException();
      }
    }

    // We could log the download here, but instead this is done via an event
    // subscriber subscribing to the KernelEvents::TERMINATE event
    // to ensure the download is logged even when directly accessing the
    // core route directly.
    $filename = $file->getFilename();
    // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
    // sets response as not cacheable if the Cache-Control header is not
    // already modified. We pass in FALSE for non-private schemes for the
    // $public parameter to make sure we don't change the headers.
    $response = new BinaryFileResponse($uri, Response::HTTP_OK, $headers, FALSE);
    if (empty($headers['Content-Disposition'])) {
      $response->setContentDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $filename
      );
    }

    return $response;
  }

}
