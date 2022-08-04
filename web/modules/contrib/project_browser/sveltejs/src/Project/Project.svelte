<script>
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let project;
  export let toggleView;
  import ActionButton from './ActionButton.svelte';
  import Image from './Image.svelte';
  import Categories from './Categories.svelte';

  const { drupalSettings, Drupal } = window;
</script>

<li class="project {toggleView.toLowerCase()}">
  <div class="top">
    <Image fieldProjectImages={project.field_project_images} />
  </div>
  <div class="main">
    <div class="middle">
      <h3>
        <a
          class="project__title"
          href={project.url}
          target="_blank"
          rel="noreferrer">{project.title}</a
        >
      </h3>
      <div class="body">{@html project.body.summary}</div>
      <Categories
        {toggleView}
        fieldModuleCategories={project.field_module_categories}
      />
    </div>
  </div>
  <div
    class="icons"
    class:warnings={project.warnings && project.warnings.length > 0}
  >
    {#if project.is_covered}
      <span>
        <img
          src="{drupalSettings.project_browser.origin_url}/{drupalSettings
            .project_browser.module_path}/images/blue-security-shield-icon.svg"
          alt=""
          title={Drupal.t('Covered by Drupal Security Team')}
          class="project-status-icon"
        />
        <!-- Show the security policy description if it is accompanied by warnings,
             since those also have descriptions.  -->
        {#if project.warnings && project.warnings.length > 0}
          <small>{Drupal.t('Covered by the security advisory policy')}</small>
        {/if}
      </span>
    {/if}
    {#if project.warnings && project.warnings.length > 0}
      {#each project.warnings as warning}
        <span>
          <img
            src="{drupalSettings.project_browser.origin_url}/{drupalSettings
              .project_browser.module_path}/images/triangle-alert.svg"
            alt=""
            class="project-status-icon"
          />
          <small>{@html warning}</small>
        </span>
      {/each}
    {/if}
    {#if toggleView === 'List'}
      <div class="container">
        <div class="image">
          <img
            src="{drupalSettings.project_browser.origin_url}/{drupalSettings
              .project_browser.module_path}/images/project-usage-icon.svg"
            alt="Project Usage"
          />
        </div>
        <div class="text">
          {project.project_usage_total.toLocaleString()} Active Installs
        </div>
      </div>
    {/if}
    <!--If there are no warnings, there is space to include the action button
        in the icons container -->
    {#if !project.warnings || project.warnings.length === 0}
      <ActionButton {project} />
    {/if}
  </div>
  <!--If there are warnings, the action button needs to be moved out of the
      icons container to provide space for the warning descriptions. -->
  {#if project.warnings && project.warnings.length > 0}
    <ActionButton {project} />
  {/if}
</li>

<style>
  /* Small devices (portrait tablets and large phones, 600px and up) */
  @media only screen and (min-width: 53.75rem) {
    .grid .top {
      overflow: hidden;
      display: flex;
      align-items: center;
    }
    .list.project {
      padding: 0 2rem;
    }
    .list .top {
      padding: 10px;
      width: 10%;
    }
  }

  /* One column card view */
  @media screen and (max-width: 75rem) {
    .grid.project {
      width: 100%;
    }
  }

  /* Two column card view (laptops/desktops, 1200px and up) */
  @media only screen and (min-width: 75rem) {
    .grid.project {
      width: 49%;
    }
    .list.project {
      width: 100%;
      padding: 0 2rem;
    }
  }

  @media only screen and (min-width: 87.5rem) {
    .grid.project {
      width: 32%;
    }
  }

  .grid.project {
    display: flex;
    flex-direction: column;
    margin-bottom: 2em;
    border: 1px solid rgba(212, 212, 218, 0.8);
    border-radius: 2px;
    box-shadow: 0 4px 10px rgb(0 0 0 / 10%);
    margin-right: 0.5vw;
  }

  .grid h3 {
    margin-top: 0.25em;
    text-align: center;
  }

  h3 a {
    text-decoration: none;
    color: black;
  }
  h3 a:hover {
    color: #003ecc;
    text-decoration: underline;
  }

  .grid .main {
    display: flex;
    background: white;
    position: relative;
    padding: 1em 1em;
    flex-direction: column;
  }

  .grid .body {
    text-align: center;
  }

  .grid .icons {
    display: flex;
    margin-top: auto;
    padding: 1em 1em;
  }
  .body {
    font-size: 15px;
  }

  .list .image {
    padding-left: 4em;
  }

  .list .image img {
    max-width: 100%;
    width: 3em;
  }
  .list .main {
    width: 100%;
  }
  .list h3 {
    padding-top: 10px;
  }
  .list.project {
    margin-bottom: 2em;
    background: #ffffff;
    border: 0.5px solid #a4a2a2;
    box-shadow: 0px 4px 4px rgba(0, 0, 0, 0.25);
  }
  .list .icons {
    display: flex;
    padding: 1em 1em 1em 0;
  }
  .icons :global(p) {
    display: inline;
  }
  .icons.warnings {
    display: block;
  }
  .icons.warnings span {
    display: list-item;
  }
  .icons.warnings img {
    display: inline;
    width: 1.2rem;
    position: relative;
    bottom: -0.25rem;
  }
  .warnings + :global(.action) {
    margin-right: 1em;
    margin-bottom: 1em;
  }
  .container {
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .project-status-icon {
    width: 2.4em;
    margin-right: 0.5em;
    display: block;
  }
</style>
