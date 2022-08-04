<script>
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let project;

  const { drupalSettings, Drupal } = window;

  /**
   * Determine is a project is present in the local Drupal codebase.
   *
   * @param {string} projectName
   *    The project name.
   * @return {boolean}
   *   True if the project is present.
   */
  function projectIsDownloaded(projectName) {
    return (
      typeof drupalSettings !== 'undefined' &&
      projectName in drupalSettings.project_browser.modules
    );
  }

  /**
   * Determine if a project is enabled/installed in the local Drupal codebase.
   *
   * @param {string} projectName
   *   The project name.
   * @return {boolean}
   *   True if the project is enabled.
   */
  function projectIsEnabled(projectName) {
    return (
      typeof drupalSettings !== 'undefined' &&
      projectName in drupalSettings.project_browser.modules &&
      drupalSettings.project_browser.modules[projectName] === 1
    );
  }

  function copyCommand(cmd) {
    const copiedCommand = document.getElementById(
      cmd === 'Download'
        ? `${project.field_project_machine_name}-download-command`
        : `${project.field_project_machine_name}-install-command`,
    );
    copiedCommand.select();
    // For mobile devices.
    copiedCommand.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copiedCommand.value);
    const copyReceipt = document.getElementById(
      cmd === 'Download'
        ? `${project.field_project_machine_name}-copied-download`
        : `${project.field_project_machine_name}-copied-install`,
    );
    copyReceipt.style.opacity = '1';
    setTimeout(() => {
      copyReceipt.style.transition = 'opacity 0.3s';
      copyReceipt.style.opacity = '0';
    }, 1000);
  }

  function getPopupMessage() {
    const download = Drupal.t('Download');
    const composerText = Drupal.t(
      'The !use_composer_open recommended way to download any Drupal module!close is with !get_composer_open Composer !close.</a>',
      {
        '!close': '</a>',
        '!use_composer_open':
          '<a href="https://www.drupal.org/docs/develop/using-composer/using-composer-to-install-drupal-and-manage-dependencies#managing-contributed" target="_blank" rel="noreferrer">',
        '!get_composer_open':
          '<a href="https://getcomposer.org/" target="_blank">',
      },
    );
    const composerExistsText = Drupal.t(
      "If you already manage your Drupal application dependencies with Composer, run the following from the command line in your application's Composer root directory",
    );
    const infoText = Drupal.t(
      'This will download the module to your codebase.',
    );
    const composerDontWorkText = Drupal.t(
      "Didn't work? !learn_open Learn how to troubleshoot Composer!close",
      {
        '!learn_open':
          '<a href="https://getcomposer.org/doc/articles/troubleshooting.md" target="_blank" rel="noreferrer">',
        '!close': '</a>',
      },
    );
    const downloadModuleText = Drupal.t(
      'If you cannot use Composer, you may !dl_manually_open download the module manually through your browser!close',
      {
        '!dl_manually_open':
          '<a href="https://www.drupal.org/docs/user_guide/en/extend-module-install.html#s-using-the-administrative-interface" target="_blank" rel="noreferrer">',
        '!close': '</a>',
      },
    );
    const install = Drupal.t('Install');
    const installText = Drupal.t(
      'To use the module you must next install/enable it. Visit the !module_page_open modules page !close to install the module using your web browser!close',
      {
        '!module_page_open': `<a href="${drupalSettings.project_browser.origin_url}/admin/modules#module-${project.field_project_machine_name}" target="_blank" rel="noreferrer">`,
        '!close': '</a>',
      },
    );
    const drushText = Drupal.t(
      'Alternatively, you can use !drush_openDrush!close to enable it via the command line.',
      {
        '!drush_open':
          '<a href="https://www.drush.org/latest/" target="_blank">',
        '!close': '</a>',
      },
    );
    const copied = Drupal.t('Copied!');
    const div = document.createElement('div');
    div.classList.add('window');
    div.innerHTML = `<h3>1. ${download}</h3>
              <p>${composerText}</p>
              <p>${composerExistsText}:</p>
              <div id="download-cmd">
                <input id="${project.field_project_machine_name}-download-command" value="composer require drupal/${project.field_project_machine_name}"/>
                <button id="download-btn"><img src="${drupalSettings.project_browser.origin_url}/${drupalSettings.project_browser.module_path}/images/copy-icon.svg" alt={Drupal.t('Copy the install command')}/></button>
                <div id="${project.field_project_machine_name}-copied-download" class="copied-download">${copied}</div>
              </div>
              <p>${infoText}</p>
              <p>${composerDontWorkText}.</p>
              <p>${downloadModuleText}.</p>
              <h3>2. ${install}</h3>
              <p>${installText}.</p>
              <p>${drushText}:</p>
              <div id="install-cmd">
                <input id="${project.field_project_machine_name}-install-command" value="drush pm-enable ${project.field_project_machine_name}"/>
                <button id="install-btn"><img src="${drupalSettings.project_browser.origin_url}/${drupalSettings.project_browser.module_path}/images/copy-icon.svg" alt={Drupal.t('Copy the install command')}/></button>
                <div id="${project.field_project_machine_name}-copied-install" class="copied-install">${copied}</div>
              </div>`;
    if (navigator.clipboard) {
      div.querySelector('#download-btn').addEventListener('click', () => {
        copyCommand('Download');
      });
      div.querySelector('#install-btn').addEventListener('click', () => {
        copyCommand('Install');
      });
    }
    return div;
  }

  function openPopup() {
    const message = getPopupMessage();
    const popupModal = Drupal.dialog(message, {
      title: project.title,
      dialogClass: 'project-browser-popup',
      width: '50rem',
    });
    popupModal.showModal();
  }
</script>

<div class="action">
  {#if !project.is_compatible}
    <span
      ><button class="button is-disabled">{Drupal.t('Not compatible')}</button
      ></span
    >
  {:else if projectIsEnabled(project.field_project_machine_name)}
    <span
      ><a
        href="{drupalSettings.project_browser
          .origin_url}/admin/modules#module-{project.field_project_machine_name}"
        target="_blank"
        rel="noreferrer"
        ><button class="button button--secondary"
          >{Drupal.t('Installed')}</button
        ></a
      ></span
    >
  {:else if projectIsDownloaded(project.field_project_machine_name)}
    <span
      ><a
        href="{drupalSettings.project_browser
          .origin_url}/admin/modules#module-{project.field_project_machine_name}"
        target="_blank"
        rel="noreferrer"
        ><button class="button button--primary">{Drupal.t('Install')}</button
        ></a
      ></span
    >
  {:else}
    <span
      ><button on:click={openPopup} class="button button--primary"
        >{Drupal.t('Download')}</button
      ></span
    >
  {/if}
</div>

<style>
  .action {
    padding: 0.5em 0;
    margin-left: auto;
  }

  .action a {
    text-decoration: none;
  }

  .button--primary,
  .button--secondary,
  .button.is-disabled {
    color: #fff;
    width: 110.53px;
    height: 24px;
    font-size: 12.65px;
    line-height: 19px;
    display: flex;
    align-items: center;
    text-align: center;
    margin: 0;
    justify-content: center;
  }

  .button--secondary {
    background-color: #575757;
  }

  /* Higher contrast because the button is conveying information that needs to be visible despite the button being disabled. */
  .button.is-disabled {
    background-color: #ebebed;
    color: #706969;
    padding-left: 0;
    padding-right: 0;
  }
</style>
