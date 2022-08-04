<script>
  export async function fetchEntity(uri) {
    let data;
    const response = await fetch(`${uri}.json`);
    if (response.ok) {
      data = await response.json();
      return data;
    }
    throw new Error('Could not load entity');
  }

  // eslint-disable-next-line import/no-mutable-exports,import/prefer-default-export
  export let fieldProjectImages;

  const { drupalSettings, Drupal } = window;
  const fallbackImage = `${drupalSettings.project_browser.origin_url}/${drupalSettings.project_browser.module_path}/images/puzzle-piece-placeholder.svg`;
  const showFallback = (ev) => {
    ev.target.src = fallbackImage;
  };
</script>

{#if typeof fieldProjectImages !== 'undefined' && fieldProjectImages.length}
  {#if fieldProjectImages[0].file.resource === 'image'}
    <img src={fieldProjectImages[0].file.uri} alt="" on:error={showFallback} />
  {:else if (fieldProjectImages[0].file.resource = 'file')}
    <!-- Keeping this block for compatibility with the mockapi. -->
    {#await fetchEntity(fieldProjectImages[0].file.uri)}
      <img src={fallbackImage} alt={Drupal.t('Placeholder')} />
    {:then file}
      <img src={file.url} alt="" on:error={showFallback} />
    {:catch error}
      <span style="color: red">{error.message}</span>
    {/await}
  {:else}
    <img src={fallbackImage} alt={Drupal.t('Placeholder')} />
  {/if}
{:else}
  <img src={fallbackImage} alt={Drupal.t('Placeholder')} />
{/if}

<style>
  img {
    display: block;
    margin-left: auto;
    margin-right: auto;
    width: 50%;
  }
  /* Small devices (portrait tablets and large phones, 600px and up) */
  @media only screen and (min-width: 600px) {
    img {
      display: block;
      width: auto;
      border-radius: 5px;
      height: 100px;
      margin-top: 20px;
    }
  }
</style>
