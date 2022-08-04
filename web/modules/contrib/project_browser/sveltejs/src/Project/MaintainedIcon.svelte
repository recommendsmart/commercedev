<script>
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let maintained;

  const { drupalSettings, Drupal } = window;
</script>

{#if maintained}
  <img
    src="{drupalSettings.project_browser.origin_url}/{drupalSettings
      .project_browser.module_path}/images/green-maintained-wrench-icon.svg"
    alt=""
    title={Drupal.t('This project is actively maintained')}
  />
{/if}

<style>
  img {
    width: 3em;
  }
</style>
