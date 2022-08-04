<script>
  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let coverage;

  const { drupalSettings, Drupal } = window;
</script>

{#if coverage}
  <img
    src="{drupalSettings.project_browser.origin_url}/{drupalSettings
      .project_browser.module_path}/images/blue-security-shield-icon.svg"
    alt=""
    title={Drupal.t('Covered by Drupal Security Team')}
  />
{/if}

<style>
  img {
    width: 3em;
    display: block;
  }
</style>
