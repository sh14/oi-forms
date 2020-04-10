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

// Listen for click on a plus button
on('change', '.js__block-type', (event) => {
  event.preventDefault()
  const select = event.target
  // search for root block element
  const parent = select.closest('.js__wp-block')
  if ('remove' === select.value) {
    // remove selected block
    parent.remove()
    // reindex all blocks
    indexDynamicFields('.js__wp-block')
  }else{
    parent.querySelector('.js__block-options').value = select.options[select.selectedIndex].getAttribute('data-options')
  }
})
