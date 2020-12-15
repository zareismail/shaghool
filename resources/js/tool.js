Nova.booting((Vue, router, store) => {
  router.addRoutes([
    {
      name: 'shaghool',
      path: '/shaghool',
      component: require('./components/Tool'),
    },
  ])
})
