// reindex all blocks
indexDynamicFields('.js__wp-block')

// Listen for click on a plus button
on('click', '.js__add-wp-block', (event) => {
  event.preventDefault()
  // search for root block element
  let parent = event.target.closest('.js__wp-block')
  // add new block after selected block
  addBlock('template__wp-block', parent, {})
  // reindex all blocks
  indexDynamicFields('.js__wp-block')
})
